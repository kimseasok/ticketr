@props(['brand', 'title' => null])
@include('portal.layouts.app', ['brand' => $brand, 'title' => $title, 'slot' => $slot])
