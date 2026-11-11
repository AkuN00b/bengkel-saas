<?php

namespace App\Http\Requests;

use App\Policies\ReportPolicy;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportRequest extends FormRequest
{
    private ?array $resolvedFilters = null;

    public function authorize(): bool
    {
        $policy = new ReportPolicy;
        $workshop = $this->attributes->get('activeWorkshop');

        return $this->routeIs('service-history.index') ? $policy->history($this->user(), $workshop) : $policy->finances($this->user(), $workshop);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->as_of) && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d$/', $this->as_of)) {
            $this->merge(['as_of' => $this->as_of.':00']);
        }
    }

    public function rules(): array
    {
        $workshop = $this->attributes->get('activeWorkshop');
        $reference = fn ($table) => ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX, Rule::exists($table, 'id')->where('workshop_id', $workshop->id)];
        $date = 'regex:/^(?!0000)\d{4}-\d{2}-\d{2}$/';

        return ['workshop_context' => ['nullable', 'integer', Rule::in([$workshop->id])],
            'preset' => ['nullable', Rule::in(['today', 'week', 'month', 'custom', 'all'])],
            'from' => ['required_if:preset,custom', 'nullable', 'date_format:Y-m-d', $date], 'to' => ['required_if:preset,custom', 'nullable', 'date_format:Y-m-d', $date],
            'mode' => ['nullable', Rule::in(['now', 'at', 'end_day'])], 'as_of' => ['required_if:mode,at', 'nullable', 'date_format:Y-m-d\TH:i:s', 'regex:/^(?!0000)\d{4}-/'],
            'cutoff_date' => ['required_if:mode,end_day', 'nullable', 'date_format:Y-m-d', $date],
            'q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'], 'method' => ['nullable', Rule::in(['all', 'cash', 'bank_transfer', 'other'])],
            'status' => ['nullable', Rule::in(['final', 'all', 'draft', 'open', 'in_progress', 'completed', 'cancelled'])],
            'date_basis' => ['nullable', Rule::in(['created', 'completed'])],
            'vehicle_id' => $reference('vehicles'), 'customer_id' => $reference('customers'),
            'mechanic_id' => ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX, Rule::exists('users', 'id')->where(fn ($q) => $q
                ->where(fn ($scope) => $scope->whereExists(fn ($m) => $m->selectRaw('1')->from('workshop_user')->whereColumn('workshop_user.user_id', 'users.id')->where('workshop_id', $workshop->id)->where('role', 'mechanic'))
                    ->orWhereExists(fn ($wo) => $wo->selectRaw('1')->from('work_orders')->whereColumn('assigned_mechanic_id', 'users.id')->where('workshop_id', $workshop->id))))],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000']];
    }

    public function filters(): array
    {
        if ($this->resolvedFilters !== null) {
            return $this->resolvedFilters;
        }
        $workshop = $this->attributes->get('activeWorkshop');
        $data = $this->validated();
        $timezone = in_array($workshop->timezone, DateTimeZone::listIdentifiers(), true) ? $workshop->timezone : 'UTC';
        $generated = CarbonImmutable::now('UTC')->startOfSecond();
        $local = $generated->timezone($timezone);
        $mode = $data['mode'] ?? 'now';
        $cutoff = $local;
        if ($mode === 'at') {
            $cutoff = CarbonImmutable::createFromFormat('!Y-m-d\TH:i:s', $data['as_of'], $timezone);
            if ($cutoff->format('Y-m-d\TH:i:s') !== $data['as_of']) {
                throw ValidationException::withMessages(['as_of' => 'Waktu lokal tidak valid dalam timezone bengkel.']);
            }
        } elseif ($mode === 'end_day') {
            $cutoff = CarbonImmutable::createFromFormat('!Y-m-d', $data['cutoff_date'], $timezone)->endOfDay()->startOfSecond();
        }
        if ($cutoff->utc()->year < 1) {
            throw ValidationException::withMessages(['as_of' => 'Waktu berada di luar batas tanggal yang didukung.']);
        }
        if ($cutoff->greaterThan($generated)) {
            throw ValidationException::withMessages(['as_of' => 'Batas waktu masa depan tidak diperbolehkan. Pilih waktu yang sudah berlalu.']);
        }
        $preset = $data['preset'] ?? 'month';
        $from = $local->startOfMonth()->toDateString();
        $to = $local->toDateString();
        if ($preset === 'today') {
            $from = $to;
        }
        if ($preset === 'week') {
            $from = $local->subDays(6)->toDateString();
        }
        if ($preset === 'custom') {
            $from = $data['from'];
            $to = $data['to'];
        }
        $all = $preset === 'all';
        if ($all && (! $this->routeIs('service-history.*') || empty($data['vehicle_id']) && empty($data['customer_id']))) {
            throw ValidationException::withMessages(['preset' => 'Semua waktu hanya tersedia pada riwayat kendaraan atau pelanggan tertentu.']);
        }
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone);
        $last = CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone);
        if ($start->utc()->year < 1 || $last->lessThan($start) || $last->greaterThan($local->startOfDay()) || $start->diffInDays($last) > 365) {
            throw ValidationException::withMessages(['from' => 'Rentang harus berurutan, maksimal 366 hari, dan tidak melewati hari ini.']);
        }
        $params = array_intersect_key($data, array_flip(['q', 'method', 'status', 'date_basis', 'vehicle_id', 'customer_id', 'mechanic_id']));
        $params += ['workshop_context' => $workshop->id, 'preset' => $all ? 'all' : 'custom', 'from' => $from, 'to' => $to, 'mode' => 'at', 'as_of' => $cutoff->format('Y-m-d\TH:i:s')];

        return $this->resolvedFilters = ['timezone' => $timezone, 'generated' => $generated, 'cutoff' => $cutoff->utc(), 'localCutoff' => $cutoff, 'start' => $start->utc(),
            'end' => $last->addDay()->utc(), 'from' => $from, 'to' => $to, 'all' => $all, 'params' => $params, 'data' => $data];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah. Buka ulang laporan sebelum melanjutkan.');
        }
        parent::failedValidation($validator);
    }
}
