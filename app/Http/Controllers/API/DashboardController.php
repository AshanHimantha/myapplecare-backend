<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\InvoiceItem;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // Get invoice stats
        $invoiceStats = [
            'today' => [
                'count' => Invoice::whereDate('created_at', $now->toDateString())->count(),
                'total' => Invoice::whereDate('created_at', $now->toDateString())->sum('total_amount')
            ],
            'week' => [
                'count' => Invoice::where('created_at', '>=', $startOfWeek)->count(),
                'total' => Invoice::where('created_at', '>=', $startOfWeek)->sum('total_amount')
            ],
            'month' => [
                'count' => Invoice::where('created_at', '>=', $startOfMonth)->count(),
                'total' => Invoice::where('created_at', '>=', $startOfMonth)->sum('total_amount')
            ],
            'year' => [
                'count' => Invoice::where('created_at', '>=', $startOfYear)->count(),
                'total' => Invoice::where('created_at', '>=', $startOfYear)->sum('total_amount')
            ],
            'all_time' => [
                'count' => Invoice::count(),
                'total' => Invoice::sum('total_amount')
            ]
        ];

        // Get completed ticket stats
        $ticketStats = [
            'today' => Ticket::where('status', 'completed')
                            ->whereDate('updated_at', $now->toDateString())
                            ->count(),
            'week' => Ticket::where('status', 'completed')
                           ->where('updated_at', '>=', $startOfWeek)
                           ->count(),
            'month' => Ticket::where('status', 'completed')
                            ->where('updated_at', '>=', $startOfMonth)
                            ->count(),
            'year' => Ticket::where('status', 'completed')
                           ->where('updated_at', '>=', $startOfYear)
                           ->count(),
            'all_time' => Ticket::where('status', 'completed')->count()
        ];

        // Get top selling products for this month
        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(invoice_items.quantity) as total_sold'))
            ->where('invoices.created_at', '>=', $startOfMonth)
            ->groupBy('products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'invoices' => $invoiceStats,
                'tickets' => $ticketStats,
                'top_products' => $topProducts
            ]
        ]);
    }
    
    public function charts()
    {
        $now = Carbon::now();
        
        // Revenue trends - Daily for current month
        $dailyRevenue = $this->getDailyRevenueData($now);
        
        // Revenue trends - Monthly for current year
        $monthlyRevenue = $this->getMonthlyRevenueData($now);
        
        // Ticket completion trend
        $ticketCompletions = $this->getTicketCompletionData($now);
        
        // Product category distribution
        $categoryDistribution = $this->getCategoryDistribution();
        
        // Sales by payment method
        $paymentMethodStats = $this->getPaymentMethodStats();
        
        // Hourly sales distribution (for popular hours)
        $hourlySales = $this->getHourlySalesDistribution();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'daily_revenue' => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'ticket_completions' => $ticketCompletions,
                'category_distribution' => $categoryDistribution,
                'payment_methods' => $paymentMethodStats,
                'hourly_sales' => $hourlySales
            ]
        ]);
    }
    
    private function getDailyRevenueData($now)
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        
        $result = Invoice::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return round($item->total, 2);
            });
            
        $dates = [];
        $values = [];
        
        // Fill in missing dates with 0 values
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $date->format('j'); // Day of month without leading zeros
            $values[] = $result[$dateString] ?? 0;
        }
        
        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Daily Revenue',
                    'data' => $values
                ]
            ]
        ];
    }
    
    private function getMonthlyRevenueData($now)
    {
        $startOfYear = $now->copy()->startOfYear();
        
        $result = Invoice::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereYear('created_at', $now->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->map(function ($item) {
                return round($item->total, 2);
            });
            
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $values = [];
        
        // Fill in all months with 0 for missing data
        for ($i = 1; $i <= 12; $i++) {
            $values[] = $result[$i] ?? 0;
        }
        
        return [
            'labels' => $monthNames,
            'datasets' => [
                [
                    'label' => 'Monthly Revenue',
                    'data' => $values
                ]
            ]
        ];
    }
    
    private function getTicketCompletionData($now)
    {
        $last30Days = $now->copy()->subDays(30);
        
        $openTickets = Ticket::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
            
        $completedTickets = Ticket::select(
                DB::raw('DATE(updated_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'completed')
            ->where('updated_at', '>=', $last30Days)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
            
        $period = CarbonPeriod::create($last30Days, $now);
        $dates = [];
        $openValues = [];
        $completedValues = [];
        
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $date->format('M d'); // Apr 01 format
            $openValues[] = $openTickets[$dateString]->count ?? 0;
            $completedValues[] = $completedTickets[$dateString]->count ?? 0;
        }
        
        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'New Tickets',
                    'data' => $openValues
                ],
                [
                    'label' => 'Completed Tickets',
                    'data' => $completedValues
                ]
            ]
        ];
    }
    
    private function getCategoryDistribution()
    {
        try {
            // Try to determine what column we can use for categorization
            $columns = Schema::getColumnListing('products');
            $categoryColumn = null;
            
            // Check for common category column names
            foreach (['category', 'type', 'product_type', 'device_type', 'device_category', 'category_name'] as $possibleColumn) {
                if (in_array($possibleColumn, $columns)) {
                    $categoryColumn = $possibleColumn;
                    break;
                }
            }
            
            // If no direct category column, check if there's a category_id column
            if (!$categoryColumn && in_array('category_id', $columns)) {
                // Use the category ID relationship
                $categories = DB::table('invoice_items')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->select('categories.name as category', DB::raw('SUM(invoice_items.quantity) as total'))
                    ->groupBy('categories.name')
                    ->get();
            } elseif ($categoryColumn) {
                // Use the direct category column
                $categories = DB::table('invoice_items')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->select("products.{$categoryColumn} as category", DB::raw('SUM(invoice_items.quantity) as total'))
                    ->groupBy("products.{$categoryColumn}")
                    ->get();
            } else {
                // If no category column found, group by product name as fallback
                $categories = DB::table('invoice_items')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->select('products.name as category', DB::raw('SUM(invoice_items.quantity) as total'))
                    ->groupBy('products.name')
                    ->limit(10)  // Limit to top 10 products to avoid too many slices
                    ->orderByDesc('total')
                    ->get();
            }
            
            $labels = [];
            $data = [];
            
            foreach ($categories as $category) {
                if (!empty($category->category)) {
                    $labels[] = ucfirst($category->category);
                    $data[] = $category->total;
                }
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data
                    ]
                ]
            ];
        } catch (\Exception $e) {
            // Fallback if any error occurs
            Log::error('Error generating category distribution: ' . $e->getMessage());
            return [
                'labels' => ['No category data available'],
                'datasets' => [
                    [
                        'data' => [0]
                    ]
                ]
            ];
        }
    }
    
    private function getPaymentMethodStats()
    {
        $paymentMethods = Invoice::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();
            
        $labels = [];
        $counts = [];
        $amounts = [];
        
        foreach ($paymentMethods as $method) {
            $labels[] = ucfirst($method->payment_method);
            $counts[] = $method->count;
            $amounts[] = round($method->total, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Transaction Count',
                    'data' => $counts
                ],
                [
                    'label' => 'Total Amount',
                    'data' => $amounts
                ]
            ]
        ];
    }
    
    private function getHourlySalesDistribution()
    {
        $hourlySales = Invoice::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');
            
        $labels = [];
        $counts = [];
        $amounts = [];
        
        // Create labels for all 24 hours
        for ($i = 0; $i < 24; $i++) {
            $labels[] = $i . ':00';
            $counts[] = $hourlySales[$i]->count ?? 0;
            $amounts[] = $hourlySales[$i]->total ?? 0;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Transaction Count',
                    'data' => $counts
                ],
                [
                    'label' => 'Total Amount',
                    'data' => $amounts
                ]
            ]
        ];
    }
}