<?php 

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Services\Workflow\NodeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    protected $nodeRegistry;
    
    public function __construct(NodeRegistry $nodeRegistry)
    {
        $this->nodeRegistry = $nodeRegistry;
    }
    
    /**
     * Display a listing of workflows
     */
    public function index()
    {
        $workflows = Workflow::where('user_id', Auth::id())
            ->withCount('nodes')
            ->withCount('executions')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
        ]);
    }
    
    /**
     * Show the form for creating a new workflow
     */
    public function create()
    {
        return Inertia::render('Workflows/Create');
    }
    
    /**
     * Store a newly created workflow
     */
    public function store(StoreWorkflowRequest $request)
    {
        $workflow = Workflow::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? false,
            'user_id' => Auth::id(),
        ]);
        
        return redirect()->route('workflows.edit', $workflow)
            ->with('success', 'Workflow created successfully');
    }
    
    /**
     * Show the workflow builder view
     */
    public function edit(Workflow $workflow)
    {
        // Authorization check
        $this->authorize('update', $workflow);
        
        // Load workflow with nodes
        $workflow->load('nodes');
        
        // Get all available node types
        $nodeTypes = $this->nodeRegistry->getNodeTypes();
        
        return Inertia::render('Workflows/Builder', [
            'workflow' => $workflow,
            'nodeTypes' => $nodeTypes,
        ]);
    }
    
    /**
     * Update the workflow
     */
    public function update(StoreWorkflowRequest $request, Workflow $workflow)
    {
        // Authorization check
        $this->authorize('update', $workflow);
        
        $workflow->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? false,
        ]);
        
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow updated successfully');
    }
    
    /**
     * Save workflow nodes
     */
    public function saveNodes(Request $request, Workflow $workflow)
    {
        // Authorization check
        $this->authorize('update', $workflow);
        
        // Validate request
        $request->validate([
            'nodes' => 'required|array',
            'nodes.*.id' => 'nullable|integer',
            'nodes.*.type' => 'required|string',
            'nodes.*.name' => 'required|string',
            'nodes.*.config' => 'required|array',
            'nodes.*.position' => 'required|array',
            'nodes.*.sequence' => 'required|integer',
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Get existing node IDs
            $existingNodeIds = $workflow->nodes()->pluck('id')->toArray();
            $updatedNodeIds = [];
            
            // Update or create nodes
            foreach ($request->nodes as $nodeData) {
                $nodeId = $nodeData['id'] ?? null;
                
                $node = $nodeId 
                    ? $workflow->nodes()->findOrFail($nodeId)
                    : new WorkflowNode();
                
                $node->fill([
                    'workflow_id' => $workflow->id,
                    'type' => $nodeData['type'],
                    'name' => $nodeData['name'],
                    'config' => $nodeData['config'],
                    'position' => $nodeData['position'],
                    'sequence' => $nodeData['sequence'],
                    'input_mapping' => $nodeData['input_mapping'] ?? null,
                    'output_mapping' => $nodeData['output_mapping'] ?? null,
                ]);
                
                $node->save();
                $updatedNodeIds[] = $node->id;
            }
            
            // Delete removed nodes
            $nodesToDelete = array_diff($existingNodeIds, $updatedNodeIds);
            
            if (!empty($nodesToDelete)) {
                $workflow->nodes()->whereIn('id', $nodesToDelete)->delete();
            }
            
            // Save connections
            if ($request->has('connections')) {
                // Clear existing connections
                foreach ($workflow->nodes as $node) {
                    $node->next_node_id = null;
                    $node->save();
                }
                
                // Create new connections
                foreach ($request->connections as $connection) {
                    $sourceNode = $workflow->nodes()->where('id', $connection['source'])->first();
                    
                    if ($sourceNode) {
                        $sourceNode->next_node_id = $connection['target'];
                        $sourceNode->save();
                    }
                }
            }
            
            DB::commit();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save workflow: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete the workflow
     */
    public function destroy(Workflow $workflow)
    {
        // Authorization check
        $this->authorize('delete', $workflow);
        
        // Delete the workflow (cascades to nodes and executions)
        $workflow->delete();
        
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow deleted successfully');
    }
    
    /**
     * Show workflow execution history
     */
    public function executions(Workflow $workflow)
    {
        // Authorization check
        $this->authorize('view', $workflow);
        
        // Load recent executions
        $executions = $workflow->executions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('Workflows/Execution', [
            'workflow' => $workflow,
            'executions' => $executions,
        ]);
    }
}
