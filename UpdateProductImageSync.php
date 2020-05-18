<?php
namespace App\Console\Commands;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class UpdateProductImageSync extends Command{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:product:image:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product imge withe varient image which has most stock';

    /**
     * Create a new command instance.
     *
     * @return void
     */
   

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement('UPDATE products p
            INNER JOIN products_variant pv ON (pv.product_id=p.id)
            INNER JOIN (
            SELECT product_id, MAX(available_quantity) AS max_quantity
            FROM products_variant pv
            GROUP BY product_id
            ) AS max_pro ON (p.id=max_pro.product_id AND pv.available_quantity=max_pro.max_quantity)
            SET p.image=pv.image');
    }
}
