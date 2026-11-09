<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.users') }}" class="p-1 rounded-lg text-slate-500 hover:bg-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Todolist Pengguna: {{ $user->name }}</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1 ml-7">{{ $user->email }} &bull; Mode Lihat Saja (Read-Only Admin)</p>
        </div>

        <div>
            <button wire:click="exportExcel" wire:loading.attr="disabled" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-700 hover:bg-slate-800 disabled:opacity-75 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportExcel">Export Excel Todolist Ini</span>
                <span wire:loading wire:target="exportExcel">Mengekspor...</span>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="w-full sm:w-72 relative">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari judul atau deskripsi..." class="w-full pl-3.5 pr-9 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div wire:loading wire:target="search,statusFilter" class="absolute right-3 top-2.5">
                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status:</label>
            <select wire:model.live="statusFilter" class="px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table of user's todos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">No</th>
                        <th class="py-3.5 px-6">Judul Tugas</th>
                        <th class="py-3.5 px-6">Deskripsi</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Deadline</th>
                        <th class="py-3.5 px-6">Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($todos as $index => $todo)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-6 text-slate-400 text-xs">
                                {{ $todos->firstItem() + $index }}
                            </td>
                            <td class="py-4 px-6 font-semibold {{ $todo->status === 'completed' ? 'line-through text-slate-400' : 'text-slate-900' }}">
                                {{ $todo->title }}
                            </td>
                            <td class="py-4 px-6 text-slate-600 max-w-xs truncate">
                                {{ $todo->description ?? '-' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ \App\Features\Todo\Enums\TodoStatus::tryFrom($todo->status)?->badgeClass() ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($todo->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500 text-xs">
                                {{ $todo->due_date ? \Carbon\Carbon::parse($todo->due_date)->format('d M Y') : '-' }}
                            </td>
                            <td class="py-4 px-6 text-slate-500 text-xs">
                                {{ $todo->created_at ? $todo->created_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                Pengguna ini belum memiliki todolist.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $todos->links() }}
        </div>
    </div>
</div>

