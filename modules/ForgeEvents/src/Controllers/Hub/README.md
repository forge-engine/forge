# ForgeWire Controller Organization Patterns

This directory demonstrates patterns for organizing ForgeWire reactive controllers to improve readability and maintainability as they grow in complexity.

## Problem

As ForgeWire controllers grow with multiple islands and actions, they can become difficult to read and maintain. The `QueueController` had 15+ action methods and 15+ state properties, making it hard to navigate.

## Solution: Action Traits Pattern

We organize related actions into reusable traits, keeping the controller focused on:
1. State properties (required for ForgeWire hydration)
2. Route definitions
3. View rendering
4. Data loading methods

## Patterns

### 1. Action Traits

Group related actions into traits:

- **`QueueJobActions`** - Individual job operations (retry, delete, trigger, view)
- **`QueueBulkActions`** - Bulk operations on selected jobs
- **`QueueFilterActions`** - Filtering and search actions
- **`QueueSelectionActions`** - Job selection management
- **`QueueSortActions`** - Sorting and pagination

### 2. Trait Implementation Methods

Traits use abstract methods to access controller state, keeping them decoupled:

```php
// In trait
abstract protected function getQueueService(): QueueHubService;
abstract protected function getSelectedJobs(): array;
abstract protected function setSelectedJobs(array $jobs): void;

// In controller
protected function getQueueService(): QueueHubService
{
    return $this->queueService;
}
```

### 3. State Properties

State properties remain on the controller (required for ForgeWire):

```php
#[State]
public string $statusFilter = '';

#[State]
public array $selectedJobs = [];
```

## Benefits

1. **Improved Readability** - Controller is now ~230 lines vs ~305 lines, with actions organized by concern
2. **Reusability** - Traits can be reused across similar controllers
3. **Maintainability** - Related actions are grouped together
4. **Testability** - Traits can be tested independently
5. **Scalability** - Easy to add new action groups without bloating the controller

## Usage Example

```php
final class QueueController
{
    use ControllerHelper;
    use ReactiveControllerHelper;
    use QueueJobActions;      // Individual job actions
    use QueueBulkActions;      // Bulk operations
    use QueueFilterActions;    // Filtering
    use QueueSelectionActions; // Selection
    use QueueSortActions;      // Sorting/pagination

    // State properties
    #[State]
    public array $selectedJobs = [];

    // Trait implementation methods
    protected function getSelectedJobs(): array
    {
        return $this->selectedJobs;
    }
}
```

## Alternative Patterns (Not Implemented)

### State DTOs

For very complex state, you could group related properties into DTOs:

```php
#[State]
public QueueFilterState $filters;

// But ForgeWire hydration would need to support this
```

### Action Handlers

For complex action logic, extract to handler classes:

```php
#[Action]
public function retryJob(int $jobId): void
{
    $this->jobActionHandler->retry($jobId, $this);
}
```

## When to Use

- ✅ Controller has 10+ action methods
- ✅ Actions can be logically grouped
- ✅ Multiple islands sharing the same controller
- ❌ Simple controllers with < 5 actions (overkill)

## Future Improvements

1. **Computed Properties** - Add `#[Computed]` attributes for derived state
2. **Action Middleware** - Add action-level middleware support
3. **State Validation** - Add validation at the state property level
4. **Action Events** - Emit events before/after actions
