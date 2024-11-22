<?php

namespace Nino\CustomQueueLaravel\Http\Controllers;

use Nino\CustomQueueLaravel\Services\QueueManager;
use Nino\CustomQueueLaravel\Services\TaskI;
use Illuminate\Http\Request;

class QueueManagerController
{
    private QueueManager $manager;

    public function __construct(QueueManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('nino-custom-queue::index', [
            'tasks' => $this->manager->getTasks()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(TaskI $customQueueTask)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TaskI $customQueueTask)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaskI $customQueueTask)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $this->manager->cancelTask($uuid);
        } catch (\Exception $e) {
            $this->manager->error_log($this->manager->formatMessage([], $e->getMessage()));
        }

        return redirect()->back();
    }
}
