<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;   // $this->authorize() bu trait'ten geliyor - Laravel 11+'da base Controller varsayilan bos, elle eklenmesi gerekiyor
}
