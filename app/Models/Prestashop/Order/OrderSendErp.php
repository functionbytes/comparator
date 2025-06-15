<?php

namespace App\Models\Prestashop\Order;

use Illuminate\Database\Eloquent\Model;

class OrderSendErp extends Model
{


    protected $connection = 'prestashop';
    protected $table = "aalv_orders_envio_gestion";
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        "id_order",
        "posible_enviar",
        "motivo_no_enviar",
        "fecha_envio",
        "error_gestion",
        "id_pedido_gestion",
        "id_usuario_gestion",
        "force_type",
    ];


}

