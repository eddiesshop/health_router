<?php

namespace App\Controllers;

class PatientsController
{
    public function index()
    {
        print('<pre>' . print_r(['INDEX'], true) . '</pre>');
    }

    public function get()
    {
        print('<pre>' . print_r(['GET', func_get_args()], true) . '</pre>');
    }

    public function create()
    {
        print('<pre>' . print_r(['CREATE', func_get_args()], true) . '</pre>');
    }

    public function update()
    {
        print('<pre>' . print_r(['PATCH', func_get_args()], true) . '</pre>');
    }

    public function delete()
    {
        print('<pre>' . print_r(['DELETE', func_get_args()], true) . '</pre>');
    }
}