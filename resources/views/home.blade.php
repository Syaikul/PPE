<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ringkasan Gudang</title>
    <!-- Menggunakan Tailwind CSS CDN untuk styling yang cepat dan modern -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Menggunakan Lucide Icons untuk ikon yang bersih -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50 font-sans text-gray-800 antialiased">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <!-- HERO / HEADER LANDING PAGE -->
        <div class="mb-8 border-b border-gray-200 pb-6 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                Daftar Gudang
            </h1>
        </div>

        <!-- GRID KOTAK-KOTAK GUDANG -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($gudangs as $gudang)
                <a href="{{ route('gudang.stok', $gudang['idgudang']) }}"
                    class="group relative rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:border-indigo-500 hover:shadow-md transition-all duration-200 block no-underline">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="rounded-lg bg-indigo-50 p-2 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <i data-lucide="warehouse" class="h-6 w-6"></i>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktif</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                        {{ $gudang['namagudang'] }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 flex items-center gap-1">
                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i> No Kontrak : {{ $gudang['nomorkontrak'] }}
                    </p>
                    <div class="mt-4 border-t border-gray-100 pt-4 flex justify-between text-xs text-gray-400">
                        <span>ID: GDG-{{ str_pad($gudang['idgudang'], 3, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-indigo-400 font-medium">Masuk →</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                    <i data-lucide="warehouse" class="mx-auto h-10 w-10 text-gray-300"></i>
                    <p class="mt-4 text-sm text-gray-500">Belum ada data gudang.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- Script untuk merender Ikon Lucide -->
    <script>
        lucide.createIcons();
    </script>
</body>

</html>
