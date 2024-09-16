<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Carrito;
use App\Models\CarritoProducto;
use App\Models\HistorialCompra;
use App\Models\HistorialProducto;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // Para generar el PDF

class CarritoController extends Controller
{
    // Añadir un producto al carrito
    public function agregar(Request $request, $productoId)
    {
        $user = Auth::user();
        $producto = Producto::findOrFail($productoId);

        // Obtén o crea un carrito para el usuario
        $carrito = $user->carrito()->firstOrCreate();

        // Añade el producto al carrito
        $carrito->productos()->attach($productoId, ['cantidad' => 1]);

        return redirect()->back()->with('success', 'Producto agregado al carrito');
    }


    // Mostrar el carrito
    public function mostrarCarrito()
    {
        $carrito = Carrito::where('user_id', Auth::id())->first();
        $productos = $carrito ? $carrito->productos : [];

        return view('carrito.mostrar', compact('productos'));
    }

    // Pagar y generar el PDF
    public function pagar()
    {
        $user = Auth::user();
        $carrito = Carrito::where('user_id', $user->id)->first();
        $productos = $carrito ? $carrito->productos : [];

        // Generar el historial de compra
        $historialCompra = HistorialCompra::create([
            'user_id' => $user->id,
            'total' => $productos->sum(fn($p) => $p->precio),
            'fecha' => now(),
        ]);

        // Guardar los productos en el historial de compra
        foreach ($productos as $producto) {
            HistorialProducto::create([
                'historial_compra_id' => $historialCompra->id,
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'precio' => $producto->precio,
            ]);
        }

        // Generar el PDF
        $pdf = Pdf::loadView('carrito.pdf', compact('user', 'productos', 'historialCompra'));

        // Vaciar el carrito después de la compra
        $carrito->productos()->detach();

        // Descargar el PDF
        return $pdf->download('compra_' . $historialCompra->id . '.pdf');
    }


    // funcion de pruebas agregar carrito 
    public function agregarAlCarrito(Request $request, $productoId)
    {
        $user = Auth::user();
        $producto = Producto::findOrFail($productoId);

        // Obtén o crea un carrito para el usuario
        $carrito = $user->carrito()->firstOrCreate();

        // Añade el producto al carrito
        $carrito->productos()->attach($productoId, ['cantidad' => 1]);

        return redirect()->back()->with('success', 'Producto agregado al carrito');
    }

}
