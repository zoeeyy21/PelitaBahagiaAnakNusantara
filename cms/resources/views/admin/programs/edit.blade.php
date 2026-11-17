<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Edit Program" :back="route('admin.programs.index')" />
    </x-slot>

    <div class="pt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl">
            <form method="POST" action="{{ route('admin.programs.update', $program) }}" enctype="multipart/form-data" class="panel p-6 motion-safe:animate-fade-up sm:p-8">
                @csrf
                @method('PUT')
                @include('admin.programs.form')
            </form>
            </div>
        </div>
    </div>
</x-app-layout>
