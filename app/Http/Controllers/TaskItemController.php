<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Models\TaskItem;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class TaskItemController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
            'name'             => 'required|string|max:255',
        ]);

        $category = TaskCategory::findOrFail($data['task_category_id']);

        $item = $category->items()->create([
            'name'       => $data['name'],
            'sort_order' => (int) $category->items()->max('sort_order') + 1,
        ]);

        ActivityLogger::log(
            action: 'created',
            description: "Added task \"{$item->name}\" to category \"{$category->name}\"",
            subject: $item,
        );

        return back()->with('success', "Task \"{$item->name}\" added.");
    }

    public function update(Request $request, TaskItem $taskItem)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $taskItem->update(['name' => $data['name']]);

        ActivityLogger::log(
            action: 'updated',
            description: "Renamed task to \"{$taskItem->name}\"",
            subject: $taskItem,
        );

        return back()->with('success', 'Task updated.');
    }

    public function destroy(TaskItem $taskItem)
    {
        $name = $taskItem->name;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted task \"{$name}\"",
            subject: $taskItem,
        );

        // Man-hour rows for this task cascade via the foreign key.
        $taskItem->delete();

        return back()->with('success', "Task \"{$name}\" deleted.");
    }
}
