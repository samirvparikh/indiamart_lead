<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Task::class);

        return view('tasks.index', [
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'customers' => \App\Models\Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->only(['search', 'status']);

        if (! $request->user()->hasAnyRole(['Super Admin', 'Admin', 'Sales Manager'])) {
            $filters['assigned_to'] = $request->user()->id;
        }

        $tasks = $this->taskService->list($filters, (int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'max:20'],
            'due_date' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        $data['assigned_by'] = $request->user()->id;
        $task = $this->taskService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'string', 'max:20'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'reminder_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $task = $this->taskService->update($task, $data);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task,
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }
}
