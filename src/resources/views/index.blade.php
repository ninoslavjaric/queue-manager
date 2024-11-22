<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <h1 class="mb-4">Task List</h1>

    <!-- Table of tasks -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Class Name</th>
            <th>Method</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Delay</th>
            <th>Retries</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tasks as $task)
            <tr>
                <td>{{ $task->getClassName() }}</td>
                <td>{{ $task->getMethod() }}</td>
                <td>{{ $task->getPriority() }}</td>
                <td>{{ $task->getStatus() }}</td>
                <td>{{ $task->getDelay() }}</td>
                <td>{{ $task->getRetries() }}</td>
                <td>
                    <!-- Only show the Cancel button if the task status is 'running' -->
                    @if($task->getStatus() === 'running')
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmCancelModal" data-url="{{ route('nino-queue-manager.destroy', ['uuid' => $task->getUuid()]) }}">
                            Cancel
                        </button>
                    @else
                        <button class="btn btn-secondary btn-sm" disabled>Cancel</button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

<!-- Modal for confirmation -->
<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmCancelModalLabel">Cancel Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to cancel this task?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form id="cancelTaskForm" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Cancel Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Use JavaScript to populate the form action with the URL from the button's data-url attribute
    var cancelButtons = document.querySelectorAll('button[data-bs-toggle="modal"]');
    cancelButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var cancelForm = document.getElementById('cancelTaskForm');
            var url = this.getAttribute('data-url');

            // Set the action URL of the form to the cancel route for the specific task
            cancelForm.action = url;
        });
    });
</script>

</body>
</html>
