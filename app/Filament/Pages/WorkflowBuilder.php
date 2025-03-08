<?php

namespace App\Filament\Pages;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Services\Workflow\NodeRegistry;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Contracts\View\View;

class WorkflowBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Workflow Builder';
    protected static ?string $title = 'Workflow Builder';
    protected static ?string $slug = 'workflow-builder';
    protected static ?int $navigationSort = 2;

    public ?Workflow $workflow = null;
    public array $workflowNodes = [];
    public array $availableNodeTypes = [];
    public array $connections = [];
    public array $selectedNode = [
        'id' => null,
        'type' => null,
        'name' => null,
        'config' => [],
    ];
    public array $canvas = [
        'scale' => 1,
        'position' => ['x' => 0, 'y' => 0],
    ];
    
    protected $listeners = [
        'nodeSelected' => 'handleNodeSelected',
        'nodeAdded' => 'handleNodeAdded',
        'nodeRemoved' => 'handleNodeRemoved',
        'nodeUpdated' => 'handleNodeUpdated',
        'connectionCreated' => 'handleConnectionCreated',
        'connectionRemoved' => 'handleConnectionRemoved',
        'canvasUpdated' => 'handleCanvasUpdated',
        'saveWorkflow' => 'handleSaveWorkflow',
    ];
    
    public function mount($workflowId = null)
    {
        if ($workflowId) {
            $this->workflow = Workflow::findOrFail($workflowId);
            $this->loadWorkflow();
        } else {
            $this->workflow = new Workflow();
            $this->workflow->name = 'New Workflow';
            $this->workflow->description = '';
            $this->workflow->is_active = false;
        }
        
        // Get available node types from registry
        $registry = app(NodeRegistry::class);
        $this->availableNodeTypes = $registry->getRegisteredNodes();
    }
    
    protected function loadWorkflow(): void
    {
        $this->workflowNodes = $this->workflow->nodes()
            ->with('connections')
            ->get()
            ->map(function ($node) {
                return [
                    'id' => $node->id,
                    'type' => $node->type,
                    'name' => $node->name,
                    'config' => $node->config,
                    'position' => $node->position,
                ];
            })
            ->toArray();
            
        $this->connections = $this->workflow->nodes()
            ->with('connections')
            ->get()
            ->flatMap(function ($node) {
                return $node->connections->map(function ($connection) use ($node) {
                    return [
                        'source' => $node->id,
                        'target' => $connection->target_node_id,
                        'sourceHandle' => $connection->source_handle,
                        'targetHandle' => $connection->target_handle,
                    ];
                });
            })
            ->toArray();
            
        if (isset($this->workflow->canvas)) {
            $this->canvas = $this->workflow->canvas;
        }
    }
    
    public function handleNodeSelected($nodeId): void
    {
        $node = collect($this->workflowNodes)->firstWhere('id', $nodeId);
        
        if ($node) {
            $this->selectedNode = [
                'id' => $node['id'],
                'type' => $node['type'],
                'name' => $node['name'],
                'config' => $node['config'] ?? [],
            ];
        } else {
            $this->selectedNode = [
                'id' => null,
                'type' => null,
                'name' => null,
                'config' => [],
            ];
        }
    }
    
    public function handleNodeAdded($node): void
    {
        $this->workflowNodes[] = $node;
        $this->handleNodeSelected($node['id']);
    }
    
    public function handleNodeRemoved($nodeId): void
    {
        // Remove node from workflowNodes
        $this->workflowNodes = collect($this->workflowNodes)
            ->filter(function ($node) use ($nodeId) {
                return $node['id'] !== $nodeId;
            })
            ->toArray();
            
        // Remove connections related to this node
        $this->connections = collect($this->connections)
            ->filter(function ($connection) use ($nodeId) {
                return $connection['source'] !== $nodeId && $connection['target'] !== $nodeId;
            })
            ->toArray();
            
        // Clear selected node if it was selected
        if ($this->selectedNode['id'] === $nodeId) {
            $this->selectedNode = [
                'id' => null,
                'type' => null,
                'name' => null,
                'config' => [],
            ];
        }
    }
    
    public function handleNodeUpdated($nodeData): void
    {
        // Update node in workflowNodes
        $this->workflowNodes = collect($this->workflowNodes)
            ->map(function ($node) use ($nodeData) {
                if ($node['id'] === $nodeData['id']) {
                    return array_merge($node, $nodeData);
                }
                return $node;
            })
            ->toArray();
            
        // Update selected node if it was updated
        if ($this->selectedNode['id'] === $nodeData['id']) {
            $this->selectedNode = array_merge($this->selectedNode, $nodeData);
        }
    }
    
    public function handleConnectionCreated($connection): void
    {
        $this->connections[] = $connection;
    }
    
    public function handleConnectionRemoved($connection): void
    {
        $this->connections = collect($this->connections)
            ->filter(function ($conn) use ($connection) {
                return !(
                    $conn['source'] === $connection['source'] &&
                    $conn['target'] === $connection['target'] &&
                    $conn['sourceHandle'] === $connection['sourceHandle'] &&
                    $conn['targetHandle'] === $connection['targetHandle']
                );
            })
            ->toArray();
    }
    
    public function handleCanvasUpdated($canvasData): void
    {
        $this->canvas = $canvasData;
    }
    
    public function handleSaveWorkflow(): void
    {
        DB::beginTransaction();
        
        try {
            // Save the workflow
            $this->workflow->canvas = $this->canvas;
            $this->workflow->save();
            
            // Delete existing nodes and connections
            if ($this->workflow->exists) {
                // Only delete nodes that are not in the current workflow nodes
                $existingNodeIds = collect($this->workflowNodes)->pluck('id')->filter()->toArray();
                
                // Delete nodes not in existingNodeIds
                WorkflowNode::where('workflow_id', $this->workflow->id)
                    ->whereNotIn('id', $existingNodeIds)
                    ->delete();
            }
            
            // Create or update nodes
            foreach ($this->workflowNodes as $nodeData) {
                $node = WorkflowNode::updateOrCreate(
                    [
                        'id' => $nodeData['id'] ?? null,
                        'workflow_id' => $this->workflow->id,
                    ],
                    [
                        'type' => $nodeData['type'],
                        'name' => $nodeData['name'],
                        'config' => $nodeData['config'] ?? [],
                        'position' => $nodeData['position'] ?? ['x' => 0, 'y' => 0],
                    ]
                );
                
                // If node ID was null, update it with the new ID
                if (empty($nodeData['id'])) {
                    foreach ($this->connections as &$connection) {
                        if ($connection['source'] === $nodeData['tempId']) {
                            $connection['source'] = $node->id;
                        }
                        if ($connection['target'] === $nodeData['tempId']) {
                            $connection['target'] = $node->id;
                        }
                    }
                }
            }
            
            // Delete all existing connections and recreate them
            DB::table('workflow_node_connections')->where('workflow_id', $this->workflow->id)->delete();
            
            // Create connections
            foreach ($this->connections as $connectionData) {
                DB::table('workflow_node_connections')->insert([
                    'workflow_id' => $this->workflow->id,
                    'source_node_id' => $connectionData['source'],
                    'target_node_id' => $connectionData['target'],
                    'source_handle' => $connectionData['sourceHandle'],
                    'target_handle' => $connectionData['targetHandle'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Workflow saved successfully')
                ->success()
                ->send();
                
            // Reload workflow to get fresh data
            $this->workflow = Workflow::find($this->workflow->id);
            $this->loadWorkflow();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error saving workflow')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function updateNodeConfig($config): void
    {
        if (!$this->selectedNode['id']) {
            return;
        }
        
        $this->handleNodeUpdated([
            'id' => $this->selectedNode['id'],
            'config' => $config,
        ]);
    }
    
    public function testWorkflow(): void
    {
        if (!$this->workflow->exists) {
            Notification::make()
                ->title('Please save the workflow first')
                ->warning()
                ->send();
            return;
        }
        
        redirect()->route('filament.admin.pages.test-workflow', ['workflowId' => $this->workflow->id]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Workflow')
                ->color('primary')
                ->action(fn () => $this->dispatch('saveWorkflow')),
                
            \Filament\Actions\Action::make('test')
                ->label('Test Workflow')
                ->color('success')
                ->action(fn () => $this->testWorkflow())
                ->visible(fn () => $this->workflow->exists),
        ];
    }
    
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.pages.workflow-builder');
    }
}
