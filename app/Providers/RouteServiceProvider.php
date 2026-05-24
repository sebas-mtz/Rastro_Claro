
namespace App\Providers;

class RouteServiceProvider extends ServiceProvider
{
    /** A dónde redirigir después de login. */
    public const HOME = '/home-custom';   // <<--- antes era '/dashboard'
    // ...
}
