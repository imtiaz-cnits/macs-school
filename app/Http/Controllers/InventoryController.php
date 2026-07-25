<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryController extends Controller
{
    /**
     * Display the stock management main view
     */
    public function index()
    {
        return view('pages.inventory.index');
    }

    /**
     * AJAX query to load inventory items
     */
    public function getItemsAjax(Request $request): JsonResponse
    {
        try {
            $query = InventoryItem::with('class');

            if ($request->filled('search')) {
                $query->where('name', 'like', "%{$request->search}%");
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }

            $items = $query->orderBy('name', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'items' => $items
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load inventory items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX action to save (create or update) inventory items
     */
    public function storeItemAjax(Request $request): JsonResponse
    {
        $request->validate([
            'id'          => 'nullable|integer|exists:inventory_items,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:asset,stationery,book',
            'class_id'    => 'nullable|required_if:type,book|exists:classes,id',
            'description' => 'nullable|string',
            'unit'        => 'required|string|max:50',
        ], [
            'class_id.required_if' => 'The class field is required when the item type is a textbook.'
        ]);

        try {
            $data = $request->only(['name', 'type', 'class_id', 'description', 'unit']);
            
            // If item type is not book, enforce null class_id
            if ($data['type'] !== 'book') {
                $data['class_id'] = null;
            }

            if ($request->filled('id')) {
                $item = InventoryItem::findOrFail($request->id);
                $item->update($data);
                $message = 'Inventory item updated successfully.';
            } else {
                $item = InventoryItem::create($data);
                $message = 'Inventory item created successfully.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'item' => $item
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save inventory item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX action to delete an item
     */
    public function deleteItemAjax($id): JsonResponse
    {
        try {
            $item = InventoryItem::findOrFail($id);
            $item->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory item deleted successfully.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete inventory item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX action to adjust (Plus / Minus) stock quantities
     */
    public function adjustStockAjax(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'type'              => 'required|in:in,out',
            'quantity'          => 'required|integer|min:1',
            'date'              => 'required|date',
            'remarks'           => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $item = InventoryItem::findOrFail($request->inventory_item_id);
            $qty = (int)$request->quantity;

            if ($request->type === 'in') {
                $item->current_quantity += $qty;
            } else {
                if ($item->current_quantity < $qty) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Insufficient stock! Current stock is only {$item->current_quantity} {$item->unit}."
                    ], 422);
                }
                $item->current_quantity -= $qty;
            }

            $item->save();

            $log = InventoryLog::create([
                'inventory_item_id' => $item->id,
                'user_id'           => Auth::id() ?? 1,
                'type'              => $request->type,
                'quantity'          => $qty,
                'date'              => $request->date,
                'remarks'           => $request->remarks,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjusted successfully.',
                'current_quantity' => $item->current_quantity
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to adjust stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX query to load audit logs/history
     */
    public function getLogsAjax(Request $request): JsonResponse
    {
        try {
            $query = InventoryLog::with(['item', 'user:id,name']);

            if ($request->filled('inventory_item_id')) {
                $query->where('inventory_item_id', $request->inventory_item_id);
            }

            $logs = $query->orderBy('date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->take(100)
                          ->get();

            return response()->json([
                'status' => 'success',
                'logs' => $logs
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load transaction history: ' . $e->getMessage()
            ], 500);
        }
    }
}
