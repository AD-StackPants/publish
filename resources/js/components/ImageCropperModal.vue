<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Crop, X, Check, RotateCw } from '@lucide/vue';

const props = defineProps<{
    isOpen: boolean;
    imageSrc: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'cropped', dataUrl: string): void;
}>();

type AspectRatioPreset = '1:1' | '4:5' | '16:9';
const selectedRatio = ref<AspectRatioPreset>('1:1');
const canvasRef = ref<HTMLCanvasElement | null>(null);
const zoom = ref(1);

const aspectRatios: {
    label: string;
    value: AspectRatioPreset;
    ratio: number;
}[] = [
    { label: '1:1 Square (Feed)', value: '1:1', ratio: 1 / 1 },
    { label: '4:5 Portrait (Insta/Meta)', value: '4:5', ratio: 4 / 5 },
    { label: '16:9 Landscape (X / Cards)', value: '16:9', ratio: 16 / 9 },
];

const renderCrop = () => {
    const canvas = canvasRef.value;
    if (!canvas || !props.imageSrc) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = props.imageSrc;

    img.onload = () => {
        const activePreset =
            aspectRatios.find((r) => r.value === selectedRatio.value) ||
            aspectRatios[0];
        const targetRatio = activePreset.ratio;

        let targetWidth = 800;
        let targetHeight = targetWidth / targetRatio;

        canvas.width = targetWidth;
        canvas.height = targetHeight;

        // Calculate source rect to fit center crop
        const imgRatio = img.width / img.height;
        let srcX = 0;
        let srcY = 0;
        let srcWidth = img.width;
        let srcHeight = img.height;

        if (imgRatio > targetRatio) {
            srcWidth = img.height * targetRatio;
            srcX = (img.width - srcWidth) / 2;
        } else {
            srcHeight = img.width / targetRatio;
            srcY = (img.height - srcHeight) / 2;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(
            img,
            srcX,
            srcY,
            srcWidth,
            srcHeight,
            0,
            0,
            targetWidth,
            targetHeight,
        );
    };
};

watch(
    () => [props.isOpen, selectedRatio.value, props.imageSrc],
    () => {
        if (props.isOpen) {
            setTimeout(renderCrop, 50);
        }
    },
);

onMounted(() => {
    if (props.isOpen) {
        renderCrop();
    }
});

const applyCrop = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const croppedUrl = canvas.toDataURL('image/jpeg', 0.92);
    emit('cropped', croppedUrl);
    emit('close');
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity"
    >
        <div
            class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-2xl"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border px-5 py-4"
            >
                <div class="flex items-center gap-2">
                    <Crop class="h-4 w-4 text-primary" />
                    <h3 class="text-sm font-semibold text-foreground">
                        Inline Image Aspect Cropper
                    </h3>
                </div>
                <button
                    @click="emit('close')"
                    class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <!-- Ratio Selector -->
                <div class="flex items-center gap-2">
                    <button
                        v-for="preset in aspectRatios"
                        :key="preset.value"
                        type="button"
                        @click="selectedRatio = preset.value"
                        class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                        :class="
                            selectedRatio === preset.value
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Preview Area -->
                <div
                    class="flex min-h-[260px] items-center justify-center overflow-hidden rounded-lg border border-border/80 bg-muted/20 p-2"
                >
                    <canvas
                        ref="canvasRef"
                        class="max-h-[360px] max-w-full rounded border border-border object-contain shadow-sm"
                    ></canvas>
                </div>

                <p class="text-center text-xs text-muted-foreground">
                    Center crop calculated automatically based on selected
                    aspect ratio.
                </p>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end gap-2.5 border-t border-border bg-muted/30 px-5 py-3"
            >
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-md border border-border bg-card px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="applyCrop"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    <Check class="h-3.5 w-3.5" />
                    Apply Crop
                </button>
            </div>
        </div>
    </div>
</template>
