<?php

namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AttributeValues;
use AuthorizesRequests;
use DB;

class Task extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable;
  public $table = "tasks";
  
  protected $fillable = [
    'user_id',
    'title',
    'description',
    'entry_by'
  ];
  

}
