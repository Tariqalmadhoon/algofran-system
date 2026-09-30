<?php

namespace App\Services;

use App\Actions\Recitations\RecordTeacherAbsenceAction;
use App\Models\Halaqa;
use App\Models\MobileDevice;
use App\Models\MobileSyncOperation;
use App\Models\TeacherAbsence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileTeacherAbsenceSyncService
{
    public function __construct(private readonly RecordTeacherAbsenceAction $recordAbsence) {}

    public function push(User $user, MobileDevice $device, array $operations): array
    {
        $results = collect($operations)
            ->map(fn (array $operation) => $this->process($user, $device, $operation))
            ->values();

        $device->forceFill(['last_seen_at' => now(), 'last_synced_at' => now()])->save();

        return [
            'results' => $results->all(),
            'summary' => [
                'total' => $results->count(),
                'accepted' => $results->whereIn('status', ['accepted', 'already_processed'])->count(),
                'rejected' => $results->where('status', 'rejected')->count(),
                'failed' => $results->where('status', 'failed')->count(),
            ],
            'server_time' => now()->toISOString(),
        ];
    }

    private function process(User $user, MobileDevice $device, array $operation): array
    {
        return DB::transaction(function () use ($user, $device, $operation): array {
            $payload = $operation['teacher_absence'];
            $hash = hash('sha256', json_encode($this->canonicalize($payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $receipt = MobileSyncOperation::query()
                ->where('mobile_device_id', $device->id)
                ->where('operation_uuid', $operation['operation_uuid'])
                ->lockForUpdate()
                ->first();

            if (! $receipt) {
                $receipt = MobileSyncOperation::query()->firstOrCreate([
                    'mobile_device_id' => $device->id,
                    'operation_uuid' => $operation['operation_uuid'],
                ], [
                    'user_id' => $user->id,
                    'operation_type' => 'teacher_absence.create',
                    'payload_hash' => $hash,
                    'status' => 'processing',
                    'client_created_at' => $operation['client_created_at'] ?? null,
                ]);

                if (! $receipt->wasRecentlyCreated) {
                    $receipt = MobileSyncOperation::query()
                        ->whereKey($receipt->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            if ($receipt && ! hash_equals($receipt->payload_hash, $hash)) {
                return $this->result($operation, 'conflict', 'تم استخدام معرّف العملية نفسه مع بيانات مختلفة.');
            }

            if ($receipt && ! in_array($receipt->status, ['failed', 'processing'], true)) {
                $response = $receipt->response ?? $this->result($operation, $receipt->status);
                if ($receipt->status === 'accepted') {
                    $response['status'] = 'already_processed';
                }

                return $response;
            }

            $receipt->forceFill([
                'status' => 'processing',
                'response' => null,
                'error_code' => null,
                'error_message' => null,
                'processed_at' => null,
            ])->save();

            try {
                $teacher = $user->teacherProfile()->where('active', true)->first();
                $halaqa = Halaqa::query()->find($payload['halaqa_id']);
                if (! $teacher || ! $halaqa) {
                    throw ValidationException::withMessages(['teacher_absence' => 'الحلقة أو ملف المحفّظ غير متاح.']);
                }

                $absence = $this->recordAbsence->execute(
                    $halaqa,
                    $teacher,
                    (string) $payload['absence_date'],
                    (string) $payload['reason'],
                    $user,
                );
                $response = [
                    'operation_uuid' => $operation['operation_uuid'],
                    'status' => 'accepted',
                    'teacher_absence' => $this->absenceArray($absence),
                ];

                return $this->complete($receipt, 'accepted', $response);
            } catch (ValidationException $exception) {
                $response = $this->result($operation, 'rejected', 'تعذر اعتماد غياب المحفّظ بعد التحقق من البيانات.');
                $response['error']['fields'] = $exception->errors();

                return $this->complete($receipt, 'rejected', $response, 'validation_failed');
            } catch (Throwable $exception) {
                Log::error('Mobile teacher absence synchronization failed.', [
                    'user_id' => $user->id,
                    'device_id' => $device->id,
                    'operation_uuid' => $operation['operation_uuid'],
                    'exception' => $exception::class,
                ]);

                return $this->complete(
                    $receipt,
                    'failed',
                    $this->result($operation, 'failed', 'تعذر إكمال المزامنة مؤقتًا، وسيعيد التطبيق المحاولة.'),
                    'temporary_failure',
                );
            }
        }, 3);
    }

    private function complete(MobileSyncOperation $receipt, string $status, array $response, ?string $errorCode = null): array
    {
        $receipt->forceFill([
            'status' => $status,
            'response' => $response,
            'error_code' => $errorCode,
            'error_message' => data_get($response, 'error.message'),
            'processed_at' => now(),
        ])->save();

        return $response;
    }

    private function result(array $operation, string $status, ?string $message = null): array
    {
        $result = ['operation_uuid' => $operation['operation_uuid'], 'status' => $status];
        if ($message) {
            $result['error'] = ['code' => $status, 'message' => $message];
        }

        return $result;
    }

    private function absenceArray(TeacherAbsence $absence): array
    {
        return [
            'id' => $absence->id,
            'halaqa_id' => $absence->halaqa_id,
            'absence_date' => $absence->absence_date->toDateString(),
            'reason' => $absence->reason,
            'updated_at' => $absence->updated_at?->toISOString(),
        ];
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
