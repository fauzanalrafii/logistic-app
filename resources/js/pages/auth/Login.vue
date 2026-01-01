<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import logo from "@/assets/images/supercorridor.png";

const form = useForm({
    username: "",
    password: "",
});

const showPassword = ref(false);

const submit = () => {
    form.post("/login");
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div
        class="min-h-screen bg-[#F3F7FA] flex items-center justify-center p-4 font-inter"
    >
        <div
            class="w-full max-w-sm sm:max-w-md md:max-w-lg bg-white shadow-xl rounded-2xl p-6 sm:p-8 md:p-10 transition-all"
        >
            <!-- LOGO & TITLE -->
            <div class="flex items-center gap-4 mb-8 justify-center">
                <div
                    class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center"
                >
                    <img
                        :src="logo"
                        alt="Supercorridor Logo"
                        class="h-12 w-12 sm:h-16 sm:w-16 md:h-20 md:w-20 object-contain"
                    />
                </div>

                <div class="text-left">
                    <h1
                        class="text-xl sm:text-2xl md:text-3xl font-extrabold text-[#002d62] tracking-wide"
                    >
                        DEPLOYMENT
                    </h1>
                    <p
                        class="text-[#0BA64A] text-xs sm:text-sm md:text-base font-medium tracking-wide"
                    >
                        AKTIVASI JARINGAN
                    </p>
                </div>
            </div>

            <!-- ERROR BOX -->
            <div
                v-if="form.errors.username || form.errors.password"
                class="mb-4 bg-red-100 text-red-700 px-3 py-2 rounded-lg border border-red-300 text-sm text-center"
            >
                {{ form.errors.username ?? form.errors.password }}
            </div>

            <!-- FORM -->
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label
                        class="block text-gray-800 mb-1 font-semibold text-sm sm:text-base"
                    >
                        Username
                    </label>
                    <input
                        v-model="form.username"
                        type="text"
                        required
                        autofocus
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0BA64A] focus:outline-none text-sm sm:text-base bg-white text-gray-900"
                        placeholder="Masukkan username"
                    />
                </div>

                <div class="mb-6">
                    <label
                        class="block text-gray-800 mb-1 font-semibold text-sm sm:text-base"
                    >
                        Password
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-[#0BA64A] focus:outline-none text-sm sm:text-base bg-white text-gray-900"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 transition"
                        >
                            <!-- Open eye -->
                            <svg
                                v-if="!showPassword"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="2.3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.5 12C4 7.5 7.8 5 12 5s8 2.5 9.5 7c-1.5 4.5-5.3 7-9.5 7s-8-2.5-9.5-7z"
                                />
                            </svg>

                            <!-- Closed eye -->
                            <svg
                                v-else
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="2.3"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 3l18 18"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.6 10.6A3 3 0 0113.4 13.4"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 8.5A10 10 0 0112 5c4.5 0 8 2.5 9.5 7a10.8 10.8 0 01-1.8 3.5"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#0BA64A] hover:bg-[#00ae4b] text-white font-semibold py-2 rounded-lg transition text-sm sm:text-base disabled:opacity-50"
                >
                    {{ form.processing ? "Memuat..." : "Login" }}
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-4 text-gray-500 text-sm">atau</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- SSO BUTTON -->
            <a
                href="#"
                class="w-full block border border-[#59b5de] text-[#59b5de] font-semibold py-3 rounded-xl text-center hover:bg-[#59b5de] hover:text-white transition active:scale-[.98] text-sm"
            >
                Login via OSS SSO
            </a>

            <p class="mt-6 text-center text-xs text-gray-500">
                © {{ new Date().getFullYear() }} Supercorridor — Your Neutral
                Network Provider
            </p>
        </div>
    </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
.font-inter {
    font-family: "Inter", sans-serif;
}
</style>
