<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Search, ChevronDown, Check, X } from 'lucide-vue-next';

export interface SelectOption {
    value: string;
    label: string;
    sublabel?: string;
}

interface Props {
    modelValue: string;
    options: SelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    allOption?: boolean;
    allLabel?: string;
    allValue?: string;
    disabled?: boolean;
    icon?: any;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Pilih salah satu',
    searchPlaceholder: 'Cari pilihan...',
    allOption: true,
    allLabel: 'Semua Pilihan',
    allValue: 'all',
    disabled: false,
    icon: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'change', value: string): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.options;
    }
    const q = searchQuery.value.toLowerCase();
    return props.options.filter(opt =>
        opt.label.toLowerCase().includes(q) ||
        (opt.sublabel && opt.sublabel.toLowerCase().includes(q))
    );
});

const currentLabel = computed(() => {
    if (props.allOption && props.modelValue === props.allValue) {
        return props.allLabel;
    }
    const found = props.options.find(opt => opt.value === props.modelValue);
    return found ? found.label : (props.modelValue ? props.modelValue : props.placeholder);
});

const currentSublabel = computed(() => {
    if (props.allOption && props.modelValue === props.allValue) {
        return null;
    }
    const found = props.options.find(opt => opt.value === props.modelValue);
    return found?.sublabel || null;
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        nextTick(() => {
            searchInputRef.value?.focus();
        });
    }
};

const selectOption = (value: string) => {
    emit('update:modelValue', value);
    emit('change', value);
    isOpen.value = false;
    searchQuery.value = '';
};

const handleClickOutside = (event: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative w-full" ref="containerRef">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-white text-left transition-all duration-150 cursor-pointer focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed"
            :class="isOpen ? 'border-orange-500 ring-2 ring-orange-500/20' : 'hover:border-slate-300'"
        >
            <div class="truncate flex items-center gap-2 min-w-0">
                <component :is="icon" v-if="icon" class="w-4 h-4 text-slate-400 shrink-0" />
                <span
                    class="truncate font-medium"
                    :class="modelValue && modelValue !== allValue ? 'text-slate-900 font-semibold' : 'text-slate-500'"
                >
                    {{ currentLabel }}
                </span>
                <span
                    v-if="currentSublabel"
                    class="text-[10px] text-slate-400 font-normal shrink-0"
                >
                    ({{ currentSublabel }})
                </span>
            </div>

            <ChevronDown
                class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200"
                :class="isOpen ? 'rotate-180 text-orange-500' : ''"
            />
        </button>

        <!-- Dropdown Popover Menu -->
        <div
            v-if="isOpen"
            class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
        >
            <!-- Search Input Box inside Dropdown -->
            <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                <div class="relative flex items-center">
                    <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" />
                    <input
                        ref="searchInputRef"
                        type="text"
                        v-model="searchQuery"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-8 pr-7 py-1.5 text-xs rounded-lg border border-slate-200 bg-white placeholder:text-slate-400 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500/30"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''; searchInputRef?.focus()"
                        class="absolute right-2 text-slate-400 hover:text-slate-600 p-0.5"
                    >
                        <X class="w-3 h-3" />
                    </button>
                </div>
            </div>

            <!-- Options List Container -->
            <div class="max-h-56 overflow-y-auto p-1 divide-y divide-slate-50">
                <!-- All Option -->
                <button
                    v-if="allOption && !searchQuery"
                    type="button"
                    @click="selectOption(allValue)"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs transition-colors cursor-pointer text-left"
                    :class="modelValue === allValue ? 'bg-orange-50 text-orange-700 font-bold' : 'text-slate-700 hover:bg-slate-50'"
                >
                    <span class="truncate">{{ allLabel }}</span>
                    <Check v-if="modelValue === allValue" class="w-3.5 h-3.5 text-orange-600 shrink-0" />
                </button>

                <!-- Filtered Options -->
                <button
                    v-for="opt in filteredOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectOption(opt.value)"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs transition-colors cursor-pointer text-left"
                    :class="modelValue === opt.value ? 'bg-orange-50 text-orange-700 font-bold' : 'text-slate-700 hover:bg-slate-50'"
                >
                    <div class="truncate min-w-0">
                        <span class="truncate block">{{ opt.label }}</span>
                        <span v-if="opt.sublabel" class="text-[10px] text-slate-400 block font-normal">
                            {{ opt.sublabel }}
                        </span>
                    </div>

                    <Check v-if="modelValue === opt.value" class="w-3.5 h-3.5 text-orange-600 shrink-0" />
                </button>

                <!-- Empty Search State -->
                <div v-if="filteredOptions.length === 0" class="py-4 text-center text-xs text-slate-400 italic">
                    Tidak ada hasil yang cocok dengan "{{ searchQuery }}"
                </div>
            </div>
        </div>
    </div>
</template>
