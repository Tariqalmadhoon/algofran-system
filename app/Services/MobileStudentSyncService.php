<?php

namespace App\Services;

use App\Actions\Students\ArchiveStudentAction;
use App\Actions\Students\CreateStudentAction;
use App\Actions\Students\UpdateStudentAction;
use App\Enums\StudentStatus;
use App\Exceptions\StudentSyncConflict;
use App\Http\Resources\Api\V1\MobileStudentResource;
use App\Models\Halaqa;
use App\Models\MobileDevice;
use App\Models\MobileSyncOperation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileStudentSyncService
{
    public function __construct(
        private readonly CreateStudentAction $createStudent,
        private readonly UpdateStudentAction $updateStudent,
        private readonly ArchiveStudentAction $archiveStudent,
        private readonly MobileTeacherScopeService $scope,
        private readonly SequentialCodeService $codes,
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

    private function process(User $user, MobileDevice $device, array $operation): array
    {
        return DB::transaction(function () use ($user, $device, $operation): array {
            $operationType = 'student.'.$operation['type'];
            $hash = $this->payloadHash([
                'type' => $operation['type'],
                'student' => $operation['student'],
            ]);
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
                    'operation_type' => $operationType,
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

            if (! hash_equals($receipt->payload_hash, $hash) || $receipt->operation_type !== $operationType) {
                return [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'conflict',
                    'error' => [
                        'code' => 'idempotency_key_reused',
                        'message' => 'استُخدم معرّف العملية نفسه مع عملية أو بيانات مختلفة.',
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
                        'message' => 'العملية نفسها قيد الاعتماد حاليًا.',
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
                $payload = $this->validatePayload($operation['type'], $operation['student']);
                $response = match ($operation['type']) {
                    'create' => $this->create($user, $payload),
                    'update' => $this->update($user, $payload),
                    'archive' => $this->archive($user, $payload),
                };

                return $this->complete($receipt, 'accepted', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'accepted',
                    'type' => $operation['type'],
                    'client_uuid' => $payload['client_uuid'] ?? null,
                    'student' => $response,
                ]);
            } catch (StudentSyncConflict $conflict) {
                return $this->complete($receipt, 'conflict', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'conflict',
                    'type' => $operation['type'],
                    'error' => [
                        'code' => $conflict->errorCode,
                        'message' => $conflict->getMessage(),
                    ],
                    'server_student' => $conflict->student ? $this->studentArray($conflict->student) : null,
                ], $conflict->errorCode);
            } catch (AuthorizationException) {
                return $this->complete($receipt, 'rejected', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'rejected',
                    'type' => $operation['type'],
                    'error' => [
                        'code' => 'student_not_available',
                        'message' => 'الطالب أو الحلقة غير متاحين ضمن نطاق المحفظ الحالي.',
                    ],
                ], 'student_not_available');
            } catch (ValidationException $exception) {
                return $this->complete($receipt, 'rejected', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'rejected',
                    'type' => $operation['type'],
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'تعذر اعتماد العملية بعد التحقق من البيانات.',
                        'fields' => $exception->errors(),
                    ],
                ], 'validation_failed');
            } catch (UniqueConstraintViolationException) {
                return $this->complete($receipt, 'conflict', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'conflict',
                    'type' => $operation['type'],
                    'error' => [
                        'code' => 'student_unique_value_exists',
                        'message' => 'توجد قيمة تعريفية مستخدمة في ملف طالب آخر.',
                    ],
                ], 'student_unique_value_exists');
            } catch (Throwable $exception) {
                Log::error('Mobile student synchronization failed.', [
                    'user_id' => $user->id,
                    'device_id' => $device->id,
                    'operation_uuid' => $operation['operation_uuid'],
                    'exception' => $exception::class,
                ]);

                return $this->complete($receipt, 'failed', [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'failed',
                    'type' => $operation['type'],
                    'error' => [
                        'code' => 'temporary_failure',
                        'message' => 'تعذر إكمال العملية مؤقتًا. سيعيد التطبيق المحاولة بأمان.',
                    ],
                ], 'temporary_failure');
            }
        }, 3);
    }

    private function create(User $user, array $payload): array
    {
        if (! Gate::forUser($user)->allows('create', Student::class)) {
            throw new AuthorizationException;
        }
        $halaqa = Halaqa::query()->find($payload['halaqa_id']);
        if (! $halaqa || ! $this->scope->canAccessHalaqa($user, $halaqa)) {
            throw new AuthorizationException;
        }
        $student = $this->createStudent->execute([
            ...collect($payload)->except('client_uuid')->all(),
            'student_number' => $this->codes->studentNumber(),
            'status' => StudentStatus::Active->value,
        ], $user);

        return $this->studentArray($student);
    }

    private function update(User $user, array $payload): array
    {
        $student = Student::query()->lockForUpdate()->find($payload['id']);
        $this->authorizeStudent($user, $student, 'update');
        $this->ensureCurrentVersion($student, $payload['base_updated_at']);
        $updates = collect($payload)->except(['id', 'client_uuid', 'base_updated_at'])->all();
        if ($updates === []) {
            throw ValidationException::withMessages(['student' => 'لا توجد تعديلات قابلة للاعتماد.']);
        }

        return $this->studentArray($this->updateStudent->execute($student, $updates, $user));
    }

    private function archive(User $user, array $payload): array
    {
        $student = Student::query()->lockForUpdate()->find($payload['id']);
        $this->authorizeStudent($user, $student, 'archive');
        $this->ensureCurrentVersion($student, $payload['base_updated_at']);

        return $this->studentArray($this->archiveStudent->execute($student, $user));
    }

    private function authorizeStudent(User $user, ?Student $student, string $ability): void
    {
        if (! $student
            || ! $this->scope->canAccessStudent($user, $student)
            || ! Gate::forUser($user)->allows($ability, $student)) {
            throw new AuthorizationException;
        }
    }

    private function ensureCurrentVersion(Student $student, string $baseUpdatedAt): void
    {
        if (! Carbon::parse($baseUpdatedAt)->equalTo($student->updated_at)) {
            throw new StudentSyncConflict(
                'student_changed_on_server',
                'تغير ملف الطالب على الخادم بعد آخر مزامنة. راجع النسخة الرسمية قبل إعادة المحاولة.',
                $student,
            );
        }
    }

    private function validatePayload(string $type, array $payload): array
    {
        $common = [
            'client_uuid' => [$type === 'create' ? 'required' : 'nullable', 'uuid'],
        ];
        $rules = match ($type) {
            'create' => $common + [
                'halaqa_id' => ['required', 'integer'],
                'first_name' => ['required', 'string', 'max:100'],
                'father_name' => ['required', 'string', 'max:100'],
                'grandfather_name' => ['required', 'string', 'max:100'],
                'family_name' => ['required', 'string', 'max:100'],
                'identity_number' => ['prohibited'],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'contact_phone' => ['nullable', 'string', 'max:30'],
                'registration_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'notes' => ['nullable', 'string', 'max:3000'],
            ],
            'update' => $common + [
                'id' => ['required', 'integer'],
                'base_updated_at' => ['required', 'date'],
                'first_name' => ['sometimes', 'required', 'string', 'max:100'],
                'father_name' => ['sometimes', 'required', 'string', 'max:100'],
                'grandfather_name' => ['sometimes', 'required', 'string', 'max:100'],
                'family_name' => ['sometimes', 'required', 'string', 'max:100'],
                'identity_number' => ['prohibited'],
                'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
                'contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
                'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
            ],
            'archive' => $common + [
                'id' => ['required', 'integer'],
                'base_updated_at' => ['required', 'date'],
            ],
        };

        return Validator::make($payload, $rules)->validate();
    }

    private function studentArray(Student $student): array
    {
        $student->loadMissing('currentHalaqa:id,name');

        return (new MobileStudentResource($student))->resolve();
    }

    private function complete(
        MobileSyncOperation $receipt,
        string $status,
        array $response,
        ?string $errorCode = null,
    ): array {
        $receipt->forceFill([
            'status' => $status,
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
}
