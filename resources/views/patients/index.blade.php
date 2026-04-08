@extends('layouts.app')

@section('title', 'Patients')
@section('page_title', 'Patients')

@section('breadcrumb')
    <li class="breadcrumb-item active">Patients</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
        <form method="GET" action="{{ route('patients.index') }}" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search name, national ID, policy…"
                   style="max-width:320px;">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request('search'))
                <a href="{{ route('patients.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            @endif
        </form>
        @can('register_patients')
        <a href="{{ route('patients.create') }}" class="btn btn-sm btn-primary ms-auto">
            <i class="bi bi-plus-lg me-1"></i> Register Patient
        </a>
        @endcan
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>National ID</th>
                    <th>DOB</th>
                    <th>Gender</th>
                    <th>Insurance</th>
                    <th>Policy #</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                <tr>
                    <td class="text-muted small">{{ $patient->id }}</td>
                    <td class="fw-medium">{{ $patient->name }}</td>
                    <td class="font-monospace small">{{ $patient->national_id }}</td>
                    <td class="small">{{ $patient->dob->format('d/m/Y') }}</td>
                    <td>
                        @if($patient->gender === 'male')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Male</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Female</span>
                        @endif
                    </td>
                    <td class="small">{{ $patient->insuranceCompany->name ?? '—' }}</td>
                    <td class="font-monospace small">{{ $patient->policy_number }}</td>
                    <td class="text-end">
                        @can('manage_admissions')
                        <a href="{{ route('admissions.create', ['patient_id' => $patient->id]) }}"
                           class="btn btn-sm btn-outline-success" title="New Admission">
                            <i class="bi bi-clipboard2-plus"></i>
                        </a>
                        @endcan
                        @can('register_patients')
                        <a href="{{ route('patients.edit', $patient) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('patients.destroy', $patient) }}"
                              class="d-inline" onsubmit="return confirm('Delete this patient? All admissions will also be removed.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No patients found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($patients->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $patients->firstItem() }}–{{ $patients->lastItem() }} of {{ $patients->total() }}</small>
        {{ $patients->links() }}
    </div>
    @endif
</div>
@endsection
