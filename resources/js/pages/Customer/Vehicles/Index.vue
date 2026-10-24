<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { Vehicle } from '../../../types';

interface Props {
    vehicles: Vehicle[];
}

const props = defineProps<Props>();

// Modal Tambah / Edit
const isFormModalOpen = ref(false);
const editingVehicle = ref<Vehicle | null>(null);

const vehicleForm = useForm({
    plate_number: '',
    brand: '',
    model: '',
    year: new Date().getFullYear(),
});

const openCreateModal = () => {
    editingVehicle.value = null;
    vehicleForm.reset();
    vehicleForm.clearErrors();
    vehicleForm.year = new Date().getFullYear();
    isFormModalOpen.value = true;
};

const openEditModal = (vehicle: Vehicle) => {
    editingVehicle.value = vehicle;
    vehicleForm.reset();
    vehicleForm.clearErrors();
    vehicleForm.plate_number = vehicle.plate_number;
    vehicleForm.brand = vehicle.brand;
    vehicleForm.model = vehicle.model;
    vehicleForm.year = vehicle.year;
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    editingVehicle.value = null;
};

const submitForm = () => {
    if (editingVehicle.value) {
        vehicleForm.put(`/beranda/vehicles/${editingVehicle.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        vehicleForm.post('/beranda/vehicles', {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
};

// Modal Hapus
const isDeleteModalOpen = ref(false);
const deletingVehicle = ref<Vehicle | null>(null);
const deleteForm = useForm({});

const openDeleteModal = (vehicle: Vehicle) => {
    deletingVehicle.value = vehicle;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    deletingVehicle.value = null;
};

const submitDelete = () => {
    if (!deletingVehicle.value) return;

    deleteForm.delete(`/beranda/vehicles/${deletingVehicle.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <AppLayout title="Garasi Kendaraan">
        <Head title="Garasi Kendaraan - ALDI-MOTOR" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        🛵 Garasi Kendaraan Saya
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola data motor Anda agar mempermudah pemesanan servis berkala tanpa input berulang.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-md shadow-red-500/20 inline-flex items-center gap-1.5 self-start sm:self-auto"
                >
                    <span>+ Daftarkan Motor Baru</span>
                </button>
            </div>

            <!-- List Kendaraan Grid -->
            <div v-if="vehicles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="vehicle in vehicles"
                    :key="vehicle.id"
                    class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-slate-300 transition flex flex-col justify-between space-y-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl font-bold border border-red-100">
                                🛵
                            </div>
                            <div>
                                <div class="font-black text-base font-mono text-slate-900 tracking-tight">
                                    {{ vehicle.plate_number }}
                                </div>
                                <div class="text-xs font-bold text-slate-700">
                                    {{ vehicle.brand }} {{ vehicle.model }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    Tahun Perakitan: {{ vehicle.year }}
                                </div>
                            </div>
                        </div>

                        <span v-if="vehicle.bookings_count && vehicle.bookings_count > 0" class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200 shrink-0">
                            {{ vehicle.bookings_count }}x Servis
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                        <Link
                            :href="`/reservasi`"
                            class="text-xs font-bold text-red-600 hover:underline"
                        >
                            + Booking Servis Motor Ini
                        </Link>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openEditModal(vehicle)"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition"
                            >
                                Ubah
                            </button>
                            <button
                                type="button"
                                @click="openDeleteModal(vehicle)"
                                class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 text-xs font-bold transition"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto mb-4">
                    🏍️
                </div>
                <h3 class="text-base font-bold text-slate-900">Garasi Anda Masih Kosong</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                    Daftarkan nomor plat dan tipe motor Anda untuk proses booking servis yang lebih cepat.
                </p>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition shadow-md shadow-red-500/20"
                >
                    + Daftarkan Motor Pertama
                </button>
            </div>
        </div>

        <!-- Modal Form Tambah / Edit Kendaraan -->
        <div v-if="isFormModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-base text-slate-900">
                        {{ editingVehicle ? 'Ubah Data Motor' : 'Daftarkan Motor Baru' }}
                    </h3>
                    <button @click="closeFormModal" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Nomor Plat Kendaraan <span class="text-red-600">*</span>
                        </label>
                        <input
                            v-model="vehicleForm.plate_number"
                            type="text"
                            placeholder="Contoh: B 6789 TGH"
                            class="w-full text-xs font-bold uppercase rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5 font-mono"
                        />
                        <div v-if="vehicleForm.errors.plate_number" class="text-xs text-rose-600 font-semibold mt-1">
                            {{ vehicleForm.errors.plate_number }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Merk <span class="text-red-600">*</span>
                            </label>
                            <input
                                v-model="vehicleForm.brand"
                                type="text"
                                placeholder="Honda / Yamaha / Suzuki"
                                class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                            />
                            <div v-if="vehicleForm.errors.brand" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ vehicleForm.errors.brand }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Tipe / Model <span class="text-red-600">*</span>
                            </label>
                            <input
                                v-model="vehicleForm.model"
                                type="text"
                                placeholder="Vario 160 / NMAX"
                                class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                            />
                            <div v-if="vehicleForm.errors.model" class="text-xs text-rose-600 font-semibold mt-1">
                                {{ vehicleForm.errors.model }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Tahun Perakitan <span class="text-red-600">*</span>
                        </label>
                        <input
                            v-model.number="vehicleForm.year"
                            type="number"
                            min="1990"
                            :max="new Date().getFullYear() + 1"
                            class="w-full text-xs rounded-xl border-slate-300 shadow-xs focus:border-red-500 focus:ring-red-500 py-2.5"
                        />
                        <div v-if="vehicleForm.errors.year" class="text-xs text-rose-600 font-semibold mt-1">
                            {{ vehicleForm.errors.year }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeFormModal"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="vehicleForm.processing"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition shadow-sm"
                        >
                            <span v-if="vehicleForm.processing">Menyimpan...</span>
                            <span v-else>{{ editingVehicle ? 'Perbarui Motor' : 'Simpan Motor' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Kendaraan -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-base text-slate-900">Hapus Kendaraan</h3>
                    <button @click="closeDeleteModal" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                </div>

                <p class="text-xs text-slate-600">
                    Apakah Anda yakin ingin menghapus motor <strong class="font-mono text-slate-900">{{ deletingVehicle?.plate_number }}</strong> ({{ deletingVehicle?.brand }} {{ deletingVehicle?.model }}) dari garasi Anda?
                </p>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50"
                    >
                        <span v-if="deleteForm.processing">Menghapus...</span>
                        <span v-else>Ya, Hapus Kendaraan</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
