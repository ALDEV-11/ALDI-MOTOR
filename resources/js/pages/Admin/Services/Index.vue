<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import InputLabel from '../../../components/InputLabel.vue';
import TextInput from '../../../components/TextInput.vue';
import InputError from '../../../components/InputError.vue';
import PrimaryButton from '../../../components/PrimaryButton.vue';

interface ServiceItem {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    price_estimate: number;
    formatted_price: string;
    reminder_interval_days: number | null;
    is_active: boolean;
    has_bookings: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedServices {
    data: ServiceItem[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    services: PaginatedServices;
    filters: {
        search?: string;
        sort_by?: string;
        sort_direction?: string;
    };
}>();

// State Pencarian
const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(
        '/admin/services',
        { search: search.value },
        { preserveState: true, replace: true }
    );
};

// Modal Tambah / Edit State
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
    duration_minutes: 45,
    price_estimate: 75000,
    reminder_interval_days: 60,
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.duration_minutes = 45;
    form.price_estimate = 75000;
    form.reminder_interval_days = 60;
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (service: ServiceItem) => {
    isEditing.value = true;
    editingId.value = service.id;
    form.clearErrors();
    form.name = service.name;
    form.description = service.description || '';
    form.duration_minutes = service.duration_minutes;
    form.price_estimate = service.price_estimate;
    form.reminder_interval_days = service.reminder_interval_days || 0;
    form.is_active = service.is_active;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/admin/services/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/services', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

// Toggle Status Aktif
const toggleActive = (service: ServiceItem) => {
    router.patch(`/admin/services/${service.id}/toggle`, {}, { preserveScroll: true });
};

// Modal Hapus State
const showDeleteModal = ref(false);
const serviceToDelete = ref<ServiceItem | null>(null);

const confirmDelete = (service: ServiceItem) => {
    serviceToDelete.value = service;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!serviceToDelete.value) return;

    router.delete(`/admin/services/${serviceToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            serviceToDelete.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Kelola Layanan Bengkel">
        <Head title="Master Layanan - Admin ALDI-MOTOR" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Master Layanan Servis</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Kelola katalog servis, estimasi durasi, harga patokan, dan siklus pengingat WhatsApp berkala.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-md shadow-red-600/20 transition"
                    >
                        <span>+ Tambah Layanan Baru</span>
                    </button>
                </div>
            </div>

            <!-- Toolbar Pencarian & Filter -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="handleSearch"
                        placeholder="Cari nama atau deskripsi servis..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="text-xs text-slate-500">
                    Total: <strong class="text-slate-800">{{ services.total }}</strong> Layanan Terdaftar
                </div>
            </div>

            <!-- Tabel Layanan -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                <th class="py-3 px-4">Nama Layanan</th>
                                <th class="py-3 px-4">Durasi Estimasi</th>
                                <th class="py-3 px-4">Estimasi Biaya</th>
                                <th class="py-3 px-4">Siklus Pengingat</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="services.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Tidak ada layanan servis yang cocok dengan kriteria pencarian.
                                </td>
                            </tr>
                            <tr
                                v-for="item in services.data"
                                :key="item.id"
                                class="hover:bg-slate-50/70 transition"
                            >
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ item.name }}</div>
                                    <div class="text-slate-500 text-[11px] max-w-sm line-clamp-1 mt-0.5">
                                        {{ item.description || '-' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                    ⏱️ {{ item.duration_minutes }} menit
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ item.formatted_price }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="item.reminder_interval_days" class="inline-flex items-center gap-1 text-slate-600 font-medium">
                                        📅 Tiap {{ item.reminder_interval_days }} hari
                                    </span>
                                    <span v-else class="text-slate-400 italic">Tanpa pengingat</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActive(item)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border transition"
                                        :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                        title="Klik untuk mengubah status"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="item.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            @click="openEditModal(item)"
                                            class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold transition"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            @click="confirmDelete(item)"
                                            class="px-2.5 py-1.5 rounded-lg border border-red-200 hover:bg-red-50 text-red-600 font-bold transition"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginasi -->
                <div v-if="services.links.length > 3" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-[11px] text-slate-500">
                        Halaman {{ services.current_page }} dari {{ services.last_page }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in services.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 text-xs rounded-lg transition"
                            :class="[
                                link.active ? 'bg-red-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed hover:bg-transparent' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH / UBAH LAYANAN -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-base text-slate-900">
                        {{ isEditing ? 'Ubah Data Layanan' : 'Tambah Layanan Servis Baru' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <div>
                        <InputLabel for="name" value="Nama Layanan Servis" />
                        <TextInput id="name" v-model="form.name" required placeholder="Contoh: Servis CVT Komplit" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Deskripsi Pengerjaan" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-slate-300 p-2.5 text-xs text-slate-800 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                            placeholder="Rincian bagian yang dicek, dibersihkan, atau diganti..."
                        ></textarea>
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="duration" value="Durasi Pengerjaan (Menit)" />
                            <TextInput id="duration" type="number" min="5" max="480" v-model="form.duration_minutes" required />
                            <InputError :message="form.errors.duration_minutes" />
                        </div>
                        <div>
                            <InputLabel for="price" value="Estimasi Biaya Jasa (Rp)" />
                            <TextInput id="price" type="number" min="0" step="5000" v-model="form.price_estimate" required />
                            <InputError :message="form.errors.price_estimate" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="reminder" value="Siklus Pengingat Servis Berkala (Hari)" />
                        <TextInput id="reminder" type="number" min="0" max="365" v-model="form.reminder_interval_days" placeholder="0 jika tidak memerlukan pengingat otomatis" />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Contoh: 30 untuk ganti oli (1 bulan), 60 untuk servis berkala (2 bulan).
                        </p>
                        <InputError :message="form.errors.reminder_interval_days" />
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-red-600 focus:ring-red-500" />
                            <span class="font-semibold text-slate-700">Layanan Aktif (Bisa Dipilih Pelanggan Saat Reservasi)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <PrimaryButton :disabled="form.processing">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Tambahkan' }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS -->
        <div v-if="showDeleteModal && serviceToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-xl font-bold mx-auto">
                    ⚠️
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-base text-slate-900">Konfirmasi Penghapusan Layanan</h3>
                    <p class="text-xs text-slate-600 mt-2">
                        Anda yakin ingin menghapus layanan <strong class="text-slate-900">"{{ serviceToDelete.name }}"</strong>?
                    </p>
                    <div v-if="serviceToDelete.has_bookings" class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 text-left">
                        ℹ️ <strong>Perhatian:</strong> Layanan ini sudah memiliki riwayat pemesanan servis. Sistem akan melakukan <strong>Arsip Aman (Soft Delete)</strong> agar riwayat servis pelanggan tetap valid.
                    </div>
                </div>

                <div class="flex items-center justify-center gap-3 pt-2">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-md shadow-red-600/20 transition"
                    >
                        Ya, Hapus Layanan
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
