<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ReturnedItem;
use App\Models\TicketItem;
use App\Models\Repair;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $data = [];
        
        // Basic counts
        $data['total_invoices'] = Invoice::count();
        $data['total_tickets'] = Ticket::count();
        $data['total_products'] = Product::count();
        
        // Sales calculations - calculate total from sold_price * quantity - discount
        $data['total_sales'] = InvoiceItem::selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_today'] = InvoiceItem::whereDate('created_at', Carbon::today())
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_month'] = InvoiceItem::whereMonth('created_at', Carbon::now()->month)
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_year'] = InvoiceItem::whereYear('created_at', Carbon::now()->year)
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_last_month'] = InvoiceItem::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_last_year'] = InvoiceItem::whereYear('created_at', Carbon::now()->subYear()->year)
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_last_7_days'] = InvoiceItem::whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_last_30_days'] = InvoiceItem::whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        $data['total_sales_last_365_days'] = InvoiceItem::whereBetween('created_at', [Carbon::now()->subDays(365), Carbon::now()])
            ->selectRaw('SUM((sold_price * quantity) - discount) as total_sales')->first()->total_sales ?? 0;
        
        // Improved profit calculations
        // Revenue: (sold_price * quantity) - discount
        // Cost: (cost_price * quantity)
        // Profit: Revenue - Cost
        $data['total_profit'] = InvoiceItem::selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as total_profit')
            ->first()->total_profit ?? 0;
        $data['total_profit_today'] = InvoiceItem::whereDate('created_at', Carbon::today())
            ->selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as total_profit')->first()->total_profit ?? 0;
        $data['total_profit_month'] = InvoiceItem::whereMonth('created_at', Carbon::now()->month)
            ->selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as total_profit')->first()->total_profit ?? 0;
        $data['total_profit_year'] = InvoiceItem::whereYear('created_at', Carbon::now()->year)
            ->selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as total_profit')->first()->total_profit ?? 0;
            
        // Total cost calculation
        $data['total_cost'] = InvoiceItem::selectRaw('SUM(cost_price * quantity) as total_cost')->first()->total_cost ?? 0;
        $data['total_cost_today'] = InvoiceItem::whereDate('created_at', Carbon::today())
            ->selectRaw('SUM(cost_price * quantity) as total_cost')->first()->total_cost ?? 0;
        $data['total_cost_month'] = InvoiceItem::whereMonth('created_at', Carbon::now()->month)
            ->selectRaw('SUM(cost_price * quantity) as total_cost')->first()->total_cost ?? 0;
        $data['total_cost_year'] = InvoiceItem::whereYear('created_at', Carbon::now()->year)
            ->selectRaw('SUM(cost_price * quantity) as total_cost')->first()->total_cost ?? 0;
        
        // Profit margin calculation (as percentage)
        if ($data['total_sales'] > 0) {
            $data['profit_margin'] = round(($data['total_profit'] / $data['total_sales']) * 100, 2);
        } else {
            $data['profit_margin'] = 0;
        }
            
        // Ticket metrics
        $data['open_tickets'] = Ticket::where('status', 'open')->count();
        $data['in_progress_tickets'] = Ticket::where('status', 'in_progress')->count();
        $data['completed_tickets'] = Ticket::where('status', 'completed')->count();
        $data['service_revenue'] = Ticket::sum('service_charge') ?? 0;
        
        // Get total revenue from repair services - fixed to use correct column name
        // First, check what columns exist in the repairs table
        $repairColumns = Schema::getColumnListing('repairs');
        $priceColumn = null;
        
        // Look for a likely price column
        $possibleColumns = ['cost', 'fee', 'price', 'charge', 'rate', 'amount'];
        foreach ($possibleColumns as $column) {
            if (in_array($column, $repairColumns)) {
                $priceColumn = $column;
                break;
            }
        }
        
        if ($priceColumn) {
            $data['repair_revenue'] = TicketItem::where('type', TicketItem::TYPE_REPAIR)  // Should be 'repair' in DB
                ->join('repairs', 'ticket_items.repair_id', '=', 'repairs.id')
                ->sum(DB::raw("repairs.$priceColumn * ticket_items.quantity")) ?? 0;
        } else {
            // Fallback if we can't find the price column
            $data['repair_revenue'] = 0;
            Log::warning('Could not find price column in repairs table. Available columns: ' . implode(', ', $repairColumns));
        }
            
        // Total units sold
        $data['total_units_sold'] = InvoiceItem::sum('quantity') ?? 0;
        
        // Returns data
        $data['total_returns'] = ReturnedItem::count();
        $data['returns_value'] = DB::table('returned_items')
            ->join('invoice_items', function($join) {
                $join->on('returned_items.invoice_id', '=', 'invoice_items.invoice_id')
                    ->on('returned_items.product_id', '=', 'invoice_items.product_id');
            })
            ->sum(DB::raw('invoice_items.sold_price * returned_items.quantity')) ?? 0;
            
        // Monthly sales trends (last 6 months)
        $data['monthly_sales'] = [];
        $data['monthly_profits'] = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $sales = InvoiceItem::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->selectRaw('SUM((sold_price * quantity) - discount) as monthly_total')->first()->monthly_total ?? 0;
                
            $profit = InvoiceItem::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as monthly_profit')
                ->first()->monthly_profit ?? 0;
            
            $data['monthly_sales'][] = [
                'month' => $month->format('M Y'),
                'sales' => $sales
            ];
            
            $data['monthly_profits'][] = [
                'month' => $month->format('M Y'),
                'profit' => $profit
            ];
        }
        
        return response()->json($data);
    }
    
    public function charts(Request $request)
    {
        $data = [];
        
        // Daily sales and profits for the last 30 days
        $data['daily_sales'] = [];
        $data['daily_profits'] = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sales = InvoiceItem::whereDate('created_at', $date)
                ->selectRaw('SUM((sold_price * quantity) - discount) as daily_total')->first()->daily_total ?? 0;
                
            $profit = InvoiceItem::whereDate('created_at', $date)
                ->selectRaw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as daily_profit')
                ->first()->daily_profit ?? 0;
                
            $data['daily_sales'][] = [
                'date' => $date->format('d M'),
                'sales' => $sales
            ];
            
            $data['daily_profits'][] = [
                'date' => $date->format('d M'),
                'profit' => $profit
            ];
        }
        
        // Top 5 selling products
        $data['top_products'] = InvoiceItem::select('product_id', 
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM((sold_price * quantity) - discount) as total_revenue'),
                DB::raw('SUM((sold_price * quantity) - discount - (cost_price * quantity)) as total_profit')
            )
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'product' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->total_quantity,
                    'revenue' => $item->total_revenue,
                    'profit' => $item->total_profit
                ];
            });
            
        // Ticket status distribution
        $data['ticket_status'] = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'completed' => Ticket::where('status', 'completed')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
        ];
        
        return response()->json($data);
    }

    /**
     * Get service center dashboard metrics with improved repair calculations
     */
    public function serviceMetrics(Request $request)
    {
        $data = [];
        
        try {
            // Basic ticket counts
            $data['total_tickets'] = Ticket::count();
            $data['open_tickets'] = Ticket::where('status', 'open')->count();
            $data['in_progress_tickets'] = Ticket::where('status', 'in_progress')->count();
            $data['completed_tickets'] = Ticket::where('status', 'completed')->count();
            
            // Service revenue - key metrics
            $data['service_revenue'] = Ticket::sum('service_charge') ?? 0;
            $data['service_revenue_today'] = Ticket::whereDate('created_at', Carbon::today())->sum('service_charge') ?? 0;
            $data['service_revenue_month'] = Ticket::whereMonth('created_at', Carbon::now()->month)->sum('service_charge') ?? 0;
            $data['service_revenue_year'] = Ticket::whereYear('created_at', Carbon::now()->year)->sum('service_charge') ?? 0;
            
            // REPAIR REVENUE - IMPROVED CALCULATION
            // First, check if we have any repairs in the system
            $repairCount = DB::table('repairs')->count();
            $repairItemCount = TicketItem::where('type', TicketItem::TYPE_REPAIR)->count();
            
            if ($repairCount > 0 && $repairItemCount > 0) {
                // Debug: Get sample repair and ticket item to verify data
                $sampleRepair = Repair::first();
                $sampleRepairItem = TicketItem::where('type', TicketItem::TYPE_REPAIR)->first();
                
                $data['debug_repair'] = [
                    'sample_repair' => $sampleRepair ? $sampleRepair->toArray() : null,
                    'sample_repair_item' => $sampleRepairItem ? $sampleRepairItem->toArray() : null,
                    'repair_count' => $repairCount,
                    'repair_item_count' => $repairItemCount
                ];
                
                // Get repair revenue - use a more explicit query with proper casting and handle NULL quantities
                $repairItems = TicketItem::where('type', TicketItem::TYPE_REPAIR)
                    ->join('repairs', 'ticket_items.repair_id', '=', 'repairs.id')
                    ->select(
                        'ticket_items.id',
                        'ticket_items.quantity',
                        'ticket_items.created_at', // Make sure we select this field
                        'repairs.cost',
                        DB::raw('repairs.cost * COALESCE(ticket_items.quantity, 1) as revenue')
                    )
                    ->get();
                    
                // Calculate totals manually to avoid SQL errors
                $totalRepairRevenue = 0;
                $monthlyRepairRevenue = 0;
                $yearlyRepairRevenue = 0;
                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;
                
                foreach ($repairItems as $item) {
                    // Convert to float and handle NULL quantity by defaulting to 1
                    $itemQuantity = $item->quantity !== null ? intval($item->quantity) : 1;
                    $itemRevenue = floatval($item->cost) * $itemQuantity;
                    $totalRepairRevenue += $itemRevenue;
                    
                    // Check if this item is from current month/year
                    if (isset($item->created_at)) { // Make sure created_at exists
                        $createdAt = Carbon::parse($item->created_at);
                        if ($createdAt->year == $currentYear) {
                            $yearlyRepairRevenue += $itemRevenue;
                            
                            if ($createdAt->month == $currentMonth) {
                                $monthlyRepairRevenue += $itemRevenue;
                            }
                        }
                    }
                }
                
                $data['repair_revenue'] = $totalRepairRevenue;
                $data['repair_revenue_month'] = $monthlyRepairRevenue;
                $data['repair_revenue_year'] = $yearlyRepairRevenue;
                
                // Include record counts for validation
                $data['repair_stats'] = [
                    'total_repairs' => $repairCount,
                    'total_repair_items' => $repairItemCount
                ];
            } else {
                $data['repair_revenue'] = 0;
                $data['repair_revenue_month'] = 0;
                $data['repair_revenue_year'] = 0;
                
                $data['repair_stats'] = [
                    'total_repairs' => $repairCount,
                    'total_repair_items' => $repairItemCount,
                    'note' => 'No repairs or repair items found in the system'
                ];
            }
            
            // Parts revenue and profit - check if parts exist
            $partsExist = DB::table('parts')->exists();
            
            if ($partsExist) {
                $partsQuery = TicketItem::where('type', TicketItem::TYPE_PART)
                    ->join('parts', 'ticket_items.part_id', '=', 'parts.id')
                    ->whereNotNull('parts.selling_price')
                    ->whereNotNull('parts.unit_price');
                    
                $data['parts_revenue'] = (clone $partsQuery)
                    ->sum(DB::raw('COALESCE(parts.selling_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_cost'] = (clone $partsQuery)
                    ->sum(DB::raw('COALESCE(parts.unit_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_profit'] = $data['parts_revenue'] - $data['parts_cost'];
                
                // Monthly parts revenue and profit
                $partsMonthQuery = (clone $partsQuery)
                    ->whereMonth('ticket_items.created_at', Carbon::now()->month);
                    
                $data['parts_revenue_month'] = (clone $partsMonthQuery)
                    ->sum(DB::raw('COALESCE(parts.selling_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_cost_month'] = (clone $partsMonthQuery)
                    ->sum(DB::raw('COALESCE(parts.unit_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_profit_month'] = $data['parts_revenue_month'] - $data['parts_cost_month'];
                
                // Yearly parts revenue and profit
                $partsYearQuery = (clone $partsQuery)
                    ->whereYear('ticket_items.created_at', Carbon::now()->year);
                    
                $data['parts_revenue_year'] = (clone $partsYearQuery)
                    ->sum(DB::raw('COALESCE(parts.selling_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_cost_year'] = (clone $partsYearQuery)
                    ->sum(DB::raw('COALESCE(parts.unit_price, 0) * COALESCE(ticket_items.quantity, 0)')) ?? 0;
                    
                $data['parts_profit_year'] = $data['parts_revenue_year'] - $data['parts_cost_year'];
            } else {
                $data['parts_revenue'] = 0;
                $data['parts_cost'] = 0;
                $data['parts_profit'] = 0;
                $data['parts_revenue_month'] = 0;
                $data['parts_cost_month'] = 0;
                $data['parts_profit_month'] = 0;
                $data['parts_revenue_year'] = 0;
                $data['parts_cost_year'] = 0;
                $data['parts_profit_year'] = 0;
            }
            
            // Total revenue (combined)
            $data['total_service_revenue'] = $data['service_revenue'] + $data['repair_revenue'] + $data['parts_revenue'];
            $data['total_service_revenue_month'] = $data['service_revenue_month'] + $data['repair_revenue_month'] + $data['parts_revenue_month'];
            $data['total_service_revenue_year'] = $data['service_revenue_year'] + $data['repair_revenue_year'] + $data['parts_revenue_year'];
            
            // Total profit (combined) - service charges and repair revenue are assumed to be all profit
            $data['total_service_profit'] = $data['service_revenue'] + $data['repair_revenue'] + $data['parts_profit'];
            $data['total_service_profit_month'] = $data['service_revenue_month'] + $data['repair_revenue_month'] + $data['parts_profit_month'];
            $data['total_service_profit_year'] = $data['service_revenue_year'] + $data['repair_revenue_year'] + $data['parts_profit_year'];
            
            // Essential item counts
            $data['total_parts_used'] = TicketItem::where('type', TicketItem::TYPE_PART)->sum('quantity') ?? 0;
            $data['total_repairs_performed'] = TicketItem::where('type', TicketItem::TYPE_REPAIR)->count();
            
            // Monthly revenue and profit summary (last 3 months)
            $data['monthly_revenue'] = [];
            $data['monthly_profit'] = [];
            
            // Additional diagnostic info
            $data['diagnostics'] = [
                'repair_type_constant' => TicketItem::TYPE_REPAIR,
                'part_type_constant' => TicketItem::TYPE_PART
            ];
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in serviceMetrics: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Return error information
            $data['error'] = $e->getMessage();
            $data['trace'] = $e->getTraceAsString();
        }
        
        return response()->json($data);
    }

    /**
     * Get detailed charts for tickets and service operations
     */
    public function ticketCharts(Request $request)
    {
        $data = [];
        
        // Daily ticket counts for the last 30 days
        $data['daily_tickets'] = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $ticketsCount = Ticket::whereDate('created_at', $date)->count();
            
            // Use updated_at instead of completed_at for completed tickets
            $completedCount = Ticket::where('status', 'completed')
                ->whereDate('updated_at', $date)
                ->count();
                
            $revenue = Ticket::whereDate('created_at', $date)->sum('service_charge') ?? 0;
            
            $data['daily_tickets'][] = [
                'date' => $date->format('d M'),
                'created' => $ticketsCount,
                'completed' => $completedCount,
                'revenue' => $revenue
            ];
        }
        
        // Ticket status distribution - unchanged
        $data['ticket_status_counts'] = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'completed' => Ticket::where('status', 'completed')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
        ];
        
        // Device category distribution - unchanged
        $data['device_categories'] = Ticket::select('device_category', DB::raw('COUNT(*) as count'))
            ->groupBy('device_category')
            ->orderByDesc('count')
            ->get();
            
        // Top 10 device models - unchanged
        $data['top_models'] = Ticket::select('device_model', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device_model')
            ->groupBy('device_model')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        
        // Top 5 repair types - unchanged
        $data['top_repairs'] = TicketItem::where('type', TicketItem::TYPE_REPAIR)  // Should be 'repair' in DB
            ->select('repair_id', DB::raw('COUNT(*) as count'))
            ->with('repair:id,repair_name,cost')
            ->groupBy('repair_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'repair_name' => $item->repair->repair_name ?? 'Unknown Repair',
                    'count' => $item->count,
                    'unit_cost' => $item->repair->cost ?? 0
                ];
            });
            
        // Top 5 parts used - ADD PROFIT CALCULATION
        $data['top_parts'] = TicketItem::where('type', TicketItem::TYPE_PART)  // Should be 'part' in DB
            ->select('part_id', DB::raw('SUM(quantity) as total_quantity'))
            ->with('part:id,part_name,selling_price,unit_price')
            ->groupBy('part_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $revenue = ($item->part->selling_price ?? 0) * $item->total_quantity;
                $cost = ($item->part->unit_price ?? 0) * $item->total_quantity;
                $profit = $revenue - $cost;
                
                return [
                    'part_name' => $item->part->part_name ?? 'Unknown Part',
                    'quantity' => $item->total_quantity,
                    'selling_price' => $item->part->selling_price ?? 0,
                    'unit_price' => $item->part->unit_price ?? 0,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit
                ];
            });
            
        // Service revenue breakdown (last 6 months) - FIX AMBIGUOUS COLUMN REFERENCES
        $data['monthly_service_revenue'] = [];
        $data['monthly_parts_revenue'] = [];
        $data['monthly_repairs_revenue'] = [];
        $data['monthly_parts_profit'] = [];
        $data['monthly_total_profit'] = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            
            // Service charges
            $serviceRevenue = Ticket::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('service_charge') ?? 0;
                
            // Parts revenue & profit
            $partsQuery = TicketItem::where('type', TicketItem::TYPE_PART)  // Should be 'part' in DB
                ->join('parts', 'ticket_items.part_id', '=', 'parts.id')
                ->whereYear('ticket_items.created_at', $month->year)
                ->whereMonth('ticket_items.created_at', $month->month);
                
            $partsRevenue = (clone $partsQuery)
                ->sum(DB::raw('parts.selling_price * ticket_items.quantity')) ?? 0;
                
            $partsCost = (clone $partsQuery)
                ->sum(DB::raw('parts.unit_price * ticket_items.quantity')) ?? 0;
                
            $partsProfit = $partsRevenue - $partsCost;
                
            // Repair revenue
            $repairsRevenue = TicketItem::where('type', TicketItem::TYPE_REPAIR)  // Should be 'repair' in DB
                ->join('repairs', 'ticket_items.repair_id', '=', 'repairs.id')
                ->whereYear('ticket_items.created_at', $month->year)
                ->whereMonth('ticket_items.created_at', $month->month)
                ->sum(DB::raw('repairs.cost * ticket_items.quantity')) ?? 0;
                
            // Assume repair is all profit (unless you have cost data for repairs)
            $repairsProfit = $repairsRevenue;
            
            // Total service center profit (service + parts + repairs)
            $totalProfit = $serviceRevenue + $partsProfit + $repairsProfit;
            
            $data['monthly_service_revenue'][] = [
                'month' => $monthLabel,
                'revenue' => $serviceRevenue
            ];
            
            $data['monthly_parts_revenue'][] = [
                'month' => $monthLabel,
                'revenue' => $partsRevenue,
                'cost' => $partsCost,
                'profit' => $partsProfit
            ];
            
            $data['monthly_repairs_revenue'][] = [
                'month' => $monthLabel,
                'revenue' => $repairsRevenue,
                'profit' => $repairsProfit
            ];
            
            $data['monthly_total_profit'][] = [
                'month' => $monthLabel,
                'profit' => $totalProfit,
                'service_profit' => $serviceRevenue,
                'parts_profit' => $partsProfit,
                'repairs_profit' => $repairsProfit
            ];
        }
        
        // Average ticket resolution time (in days) - FIXED to use updated_at
        $data['avg_resolution_time'] = Ticket::where('status', 'completed')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)/24) as avg_days'))
            ->first()->avg_days ?? 0;
            
        // Ticket priority distribution - unchanged
        $data['priority_distribution'] = Ticket::select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        // Service center efficiency - unchanged
        $totalCompleted = $data['ticket_status_counts']['completed'];
        $totalTickets = array_sum($data['ticket_status_counts']);
        $data['completion_rate'] = $totalTickets > 0 ? 
            round(($totalCompleted / $totalTickets) * 100, 2) : 0;
        
        // Add overall profit calculations
        $data['service_center_revenue'] = [
            'service_charges' => Ticket::sum('service_charge') ?? 0,
            'parts_revenue' => TicketItem::where('type', TicketItem::TYPE_PART)  // Should be 'part' in DB
                ->join('parts', 'ticket_items.part_id', '=', 'parts.id')
                ->sum(DB::raw('parts.selling_price * ticket_items.quantity')) ?? 0,
            'repairs_revenue' => TicketItem::where('type', TicketItem::TYPE_REPAIR)  // Should be 'repair' in DB
                ->join('repairs', 'ticket_items.repair_id', '=', 'repairs.id')
                ->sum(DB::raw('repairs.cost * ticket_items.quantity')) ?? 0
        ];
        
        $data['service_center_cost'] = [
            'parts_cost' => TicketItem::where('type', TicketItem::TYPE_PART)  // Should be 'part' in DB
                ->join('parts', 'ticket_items.part_id', '=', 'parts.id')
                ->sum(DB::raw('parts.unit_price * ticket_items.quantity')) ?? 0
        ];
        
        $data['service_center_profit'] = [
            'service_profit' => $data['service_center_revenue']['service_charges'],
            'parts_profit' => $data['service_center_revenue']['parts_revenue'] - $data['service_center_cost']['parts_cost'],
            'repairs_profit' => $data['service_center_revenue']['repairs_revenue'],
            'total_profit' => $data['service_center_revenue']['service_charges'] + 
                             ($data['service_center_revenue']['parts_revenue'] - $data['service_center_cost']['parts_cost']) +
                             $data['service_center_revenue']['repairs_revenue']
        ];
            
        return response()->json($data);
    }
}