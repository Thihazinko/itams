<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class TaskCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'plan_hours' => 'nullable|numeric|min:0|max:999999',
        ]);

        $category = TaskCategory::create([
            'name'       => $data['name'],
            'plan_hours' => $data['plan_hours'] ?? 0,
            'sort_order' => (int) TaskCategory::max('sort_order') + 1,
        ]);

        ActivityLogger::log(
            action: 'created',
            description: "Added Task Management category \"{$category->name}\"",
            subject: $category,
        );

        return back()->with('success', "Category \"{$category->name}\" added.");
    }

    public function update(Request $request, TaskCategory $taskCategory)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'plan_hours' => 'nullable|numeric|min:0|max:999999',
        ]);

        $taskCategory->update([
            'name'       => $data['name'],
            'plan_hours' => $data['plan_hours'] ?? 0,
        ]);

        ActivityLogger::log(
            action: 'updated',
            description: "Updated Task Management category \"{$taskCategory->name}\"",
            subject: $taskCategory,
        );

        return back()->with('success', 'Category updated.');
    }

    public function destroy(TaskCategory $taskCategory)
    {
        $name = $taskCategory->name;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted Task Management category \"{$name}\" (and its tasks)",
            subject: $taskCategory,
        );

        // Tasks and their man-hour rows cascade via the foreign keys.
        $taskCategory->delete();

        return back()->with('success', "Category \"{$name}\" and its tasks deleted.");
    }
}
