<?php

namespace App\Services;

use App\Actions\Recitations\RecordStudentDailyRecordAction;
use App\Enums\AttendanceStatus;
use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Http\Resources\Api\V1\DailyRecordResource;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\MobileDevice;
use App\Models\MobileSyncOperation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileDailySyncService
{
    public function __construct(
        private readonly RecordStudentDailyRecordAction $recordDaily,
        private readonly MobileSyncCursor $cursor,
    ) {}

    public function push(User $user, MobileDevice $device, array $operations): array
    {
        $results = collect($operations)
            ->map(fn (array $operation) => $this->process($user, $device, $operation))
            ->values();

        $device->forceFill([
            'last_seen_at' => now(),
            'last_synced_at' => now(),
        ])->save();

        return [
            'results' => $results->all(),
            'summary' => [
                'total' => $results->count(),
                'accepted' => $results->whereIn('status', ['accepted', 'already_processed'])->count(),
                'conflicts' => $results->where('status', 'conflict')->count(),
                'rejected' => $results->where('status', 'rejected')->count(),
                'failed' => $results->where('status', 'failed')->count(),
            ],
            'server_time' => now()->toISOString(),
        ];
    }

    public function changes(User $user, ?string $encodedCursor, int $limit): array
    {
        $teacher = $user->teacherProfile()->where('active', true)->first();
        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher' => 'لا يوجد ملف محفّظ فعّال لهذا الحساب.',
            ]);
        }

        $point = $encodedCursor ? $this->cursor->decode($encodedCursor) : null;
        $query = DailyRecord::query()
            ->where('teacher_profile_id', $teacher->id)
            ->with([
                'student:id,student_number,full_name',
                'halaqa:id,name',
                'teacher.user:id,name',
                'attendance',
                'recitationItems.startAyah.surah',
                'recitationItems.endAyah.surah',
            ]);

        if ($point) {
            $query->where(function ($changes) use ($point) {
                $changes->where('updated_at', '>', $point['updated_at'])
                    ->orWhere(function ($sameTimestamp) use ($point) {
                        $sameTimestamp->where('updated_at', '=', $point['updated_at'])
                            ->where('id', '>', $point['id']);
                    });
            });
        }

        /** @var Collection<int, DailyRecord> $records */
        $records = $query->orderBy('updated_at')->orderBy('id')->limit($limit + 1)->get();
        $hasMore = $records->count() > $limit;
        if ($hasMore) {
            $records->pop();
        }

        $last = $records->last();

        return [
            'records' => $records->map(fn (DailyRecord $record) => $this->recordArray($record))->all(),
            'next_cursor' => $last ? $this->cursor->fromRecord($last) : ($encodedCursor ?: $this->cursor->fromRecord()),
            'has_more' => $hasMore,
            'server_time' => now()->toISOString(),
        ];
    }

    private function process(User $user, MobileDevice $device, array $operation): array
    {
        return DB::transaction(function () use ($user, $device, $operation): array {
            $hash = $this->payloadHash($operation['daily_record']);
            $receipt = MobileSyncOperation::query()
                ->where('mobile_device_id', $device->id)
                ->where('operation_uuid', $operation['operation_uuid'])
                ->lockForUpdate()
                ->first();

            $receiptWasCreated = false;

            if (! $receipt) {
                $receipt = MobileSyncOperation::query()->firstOrCreate([
                    'mobile_device_id' => $device->id,
                    'operation_uuid' => $operation['operation_uuid'],
                ], [
                    'user_id' => $user->id,
                    'operation_type' => 'daily_record.create',
                    'payload_hash' => $hash,
                    'status' => 'processing',
                    'client_created_at' => $operation['client_created_at'] ?? null,
                ]);
                $receiptWasCreated = $receipt->wasRecentlyCreated;

                if (! $receiptWasCreated) {
                    $receipt = MobileSyncOperation::query()
                        ->whereKey($receipt->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            if (! hash_equals($receipt->payload_hash, $hash)) {
                return [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'conflict',
                    'error' => [
                        'code' => 'idempotency_key_reused',
                        'message' => 'استُخدم معرّف العملية نفسه مع بيانات مختلفة. لم يتم اعتماد السجل الجديد.',
                    ],
                ];
            }

            if (! $receiptWasCreated
                && $receipt->status === 'processing'
                && $receipt->updated_at?->isAfter(now()->subMinutes(2))) {
                return [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'failed',
                    'error' => [
                        'code' => 'operation_in_progress',
                        'message' => 'العملية نفسها قيد الاعتماد حاليًا. سيعيد التطبيق التحقق تلقائيًا.',
                    ],
                ];
            }

            if (! $receiptWasCreated && ! in_array($receipt->status, ['failed', 'processing'], true)) {
                return $this->replay($receipt);
            }

            if (! $receiptWasCreated) {
                $receipt->forceFill([
                    'status' => 'processing',
                    'response' => null,
                    'error_code' => null,
                    'error_message' => null,
                    'processed_at' => null,
                ])->save();
            }

            try {
                $payload = $this->validatePayload($operation['daily_record']);
                $teacher = $user->teacherProfile()->where('active', true)->first();
                if (! $teacher) {
                    throw ValidationException::withMessages([
                        'teacher' => 'لا يوجد ملف محفّظ فعّال لهذا الحساب.',
                    ]);
                }

                $student = Student::query()->find($payload['student_id']);
                $halaqa = Halaqa::query()->find($payload['halaqa_id']);
                if (! $student || ! $halaqa) {
                    throw ValidationException::withMessages([
                        'daily_record' => 'الطالب أو الحلقة لم يعد متاحًا على الخادم.',
                    ]);
                }

                $this->recordDaily->ensureCanRecord(
                    $student,
                    $halaqa,
                    $teacher,
                    (string) $payload['record_date'],
                    $user,
                );

                $existing = $this->existingRecord((int) $payload['student_id'], (string) $payload['record_date']);
                if ($existing) {
                    return $this->complete($receipt, 'conflict', [
                        'operation_uuid' => $operation['operation_uuid'],
                        'status' => 'conflict',
                        'error' => [
                            'code' => 'daily_record_already_exists',
                            'message' => 'يوجد سجل رسمي لهذا الطالب في التاريخ المحدد.',
                        ],
                        'server_record' => $this->recordArray($existing),
                    ], $existing, 'daily_record_already_exists');
                }

                $record = $this->recordDaily->execute($student, $halaqa, $teacher, [
                    'record_date' => $payload['record_date'],
                    'attendance_status' => $payload['attendance_status'],
                    'attendance_notes' => $payload['attendance_notes'] ?? null,
                    'general_evaluation' => $payload['general_evaluation'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'items' => collect($payload['items'])->map(fn (array $item) => [
                        'enabled' => true,
                        'type' => $item['type'],
                        'start_ayah_id' => $item['start_ayah_id'],
                        'end_ayah_id' => $item['end_ayah_id'],
                        'evaluation' => $item['evaluation'],
                        'notes' => $item['notes'] ?? null,
                        'memorization_errors' => $item['memorization_errors'] ?? 0,
                        'tajweed_errors' => $item['tajweed_errors'] ?? 0,
                        'hesitation_count' => $item['hesitation_count'] ?? 0,
                        'teacher_prompt_count' => $item['teacher_prompt_count'] ?? 0,
                    ])->all(),
                ], $user);

                return $this->complete($receipt, 'accepted', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'accepted',
                    'record' => $this->recordArray($record),
                ], $record);
            } catch (ValidationException $exception) {
                return $this->complete($receipt, 'rejected', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'rejected',
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'تعذر اعتماد السجل بعد التحقق من الصلاحيات والبيانات.',
                        'fields' => $exception->errors(),
                    ],
                ], errorCode: 'validation_failed');
            } catch (UniqueConstraintViolationException) {
                $rawPayload = $operation['daily_record'];
                $existing = isset($rawPayload['student_id'], $rawPayload['record_date'])
                    ? $this->existingRecord((int) $rawPayload['student_id'], (string) $rawPayload['record_date'])
                    : null;

                return $this->complete($receipt, 'conflict', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'conflict',
                    'error' => [
                        'code' => 'daily_record_already_exists',
                        'message' => 'اعتمد الخادم سجلًا آخر لهذا الطالب في الوقت نفسه.',
                    ],
                    'server_record' => $existing ? $this->recordArray($existing) : null,
                ], $existing, 'daily_record_already_exists');
            } catch (Throwable $exception) {
                Log::error('Mobile daily record synchronization failed.', [
                    'user_id' => $user->id,
                    'device_id' => $device->id,
                    'operation_uuid' => $operation['operation_uuid'],
                    'exception' => $exception::class,
                ]);

                return $this->complete($receipt, 'failed', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'failed',
                    'error' => [
                        'code' => 'temporary_failure',
                        'message' => 'تعذر إكمال المزامنة مؤقتًا. سيعيد التطبيق المحاولة بأمان.',
                    ],
                ], errorCode: 'temporary_failure');
            }
        }, 3);
    }

    private function existingRecord(int $studentId, string $date): ?DailyRecord
    {
        return DailyRecord::query()
            ->where('student_id', $studentId)
            ->whereDate('record_date', $date)
            ->with([
                'student:id,student_number,full_name',
                'halaqa:id,name',
                'teacher.user:id,name',
                'attendance',
                'recitationItems.startAyah.surah',
                'recitationItems.endAyah.surah',
            ])
            ->first();
    }

    private function validatePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'halaqa_id' => ['required', 'integer', 'exists:halaqas,id'],
            'record_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'attendance_status' => ['required', Rule::enum(AttendanceStatus::class)],
            'attendance_notes' => ['nullable', 'string', 'max:2000'],
            'general_evaluation' => ['nullable', Rule::enum(EvaluationRating::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['present', 'array', 'max:6'],
            'items.*.type' => ['required', Rule::enum(RecitationType::class)],
            'items.*.start_ayah_id' => ['required', 'integer', 'exists:quran_ayahs,id'],
            'items.*.end_ayah_id' => ['required', 'integer', 'exists:quran_ayahs,id'],
            'items.*.evaluation' => ['required', Rule::enum(EvaluationRating::class)],
            'items.*.memorization_errors' => ['sometimes', 'integer', 'between:0,999'],
            'items.*.tajweed_errors' => ['sometimes', 'integer', 'between:0,999'],
            'items.*.hesitation_count' => ['sometimes', 'integer', 'between:0,999'],
            'items.*.teacher_prompt_count' => ['sometimes', 'integer', 'between:0,999'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            $status = AttendanceStatus::tryFrom((string) ($payload['attendance_status'] ?? ''));
            if ($status?->isAbsence() && ! empty($payload['items'])) {
                $validator->errors()->add('items', 'لا يمكن مزامنة تسميع لطالب غائب، سواء كان الغياب بعذر أو دون عذر.');
            }
        });

        return $validator->validate();
    }

    private function complete(
        MobileSyncOperation $receipt,
        string $status,
        array $response,
        ?DailyRecord $record = null,
        ?string $errorCode = null,
    ): array {
        $receipt->forceFill([
            'status' => $status,
            'daily_record_id' => $record?->id,
            'response' => $response,
            'error_code' => $errorCode,
            'error_message' => data_get($response, 'error.message'),
            'processed_at' => now(),
        ])->save();

        return $response;
    }

    private function replay(MobileSyncOperation $receipt): array
    {
        $response = $receipt->response ?? [
            'operation_uuid' => $receipt->operation_uuid,
            'status' => $receipt->status,
        ];

        if ($receipt->status === 'accepted') {
            $response['original_status'] = 'accepted';
            $response['status'] = 'already_processed';
        }

        return $response;
    }

    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalize($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }

    private function recordArray(DailyRecord $record): array
    {
        $record->loadMissing([
            'student:id,student_number,full_name',
            'halaqa:id,name',
            'teacher.user:id,name',
            'attendance',
            'recitationItems.startAyah.surah',
            'recitationItems.endAyah.surah',
        ]);

        return (new DailyRecordResource($record))->resolve();
    }
}
