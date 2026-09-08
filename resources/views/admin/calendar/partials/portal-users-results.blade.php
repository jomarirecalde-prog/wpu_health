@php
    $filtered = request()->filled('q') || request()->filled('status') || request()->filled('patient_type_id');
@endphp
<div class="table-responsive">
    <table class="data-table" id="portal-users-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Email</th>
                <th>Student/Employee ID</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td data-label="Patient">
                        <div class="portal-user-cell">
                            <x-portal-user-avatar :user="$u" size="topbar" />
                            <span class="portal-user-cell__name">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td data-label="Email">{{ $u->email }}</td>
                    <td data-label="ID">{{ $u->employee_student_id ?: '—' }}</td>
                    <td data-label="Status">
                        <span class="status-badge {{ $u->statusBadgeClass() }}">
                            <i class="fas fa-circle" aria-hidden="true"></i>
                            {{ ucfirst($u->status) }}
                        </span>
                    </td>
                    <td data-label="Registered">{{ $u->created_at?->format('M j, Y') }}</td>
                    <td data-label="Actions">
                        <div class="table-actions">
                            <a href="{{ route('admin.calendar.portal-users.show', $u) }}" class="btn btn-view btn-icon btn-sm" title="View">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="sr-only">View</span>
                            </a>
                            <a href="{{ route('admin.calendar.portal-users.edit', $u) }}" class="btn btn-edit btn-icon btn-sm" title="Edit">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span class="sr-only">Edit</span>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="table-empty">
                            <i class="fas fa-users table-empty__icon" aria-hidden="true"></i>
                            @if($filtered)
                                <p class="table-empty__title">No matching patients</p>
                                <p class="table-empty__hint">Try a different name, email, ID, or clear the filters.</p>
                            @else
                                <p class="table-empty__title">No Portal Users</p>
                                <p class="table-empty__hint">There are currently no patient accounts registered for consultation booking.</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($users->hasPages())
    <div class="pagination">{{ $users->links() }}</div>
@endif
