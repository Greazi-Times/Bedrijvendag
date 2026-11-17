<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { createSlideSync, nextIndex, preload } from '@/lib/slideSync';

const fallbackImages = [
    '/images/slides/Bedrijvendag-maart-26-2026-3.png',
    '/images/slides/Bedrijvendag-maart-26-2026-4.png',
    '/images/slides/Bedrijvendag-maart-26-2026-5.png',
    '/images/slides/Bedrijvendag-maart-26-2026-6.png',
    '/images/slides/Bedrijvendag-maart-26-2026-7.png',
];

const props = defineProps<{
    slideImages: string[];
    slideVersion: string;
}>();

const images = ref(props.slideImages.length > 0 ? props.slideImages : fallbackImages);
const currentIndex = ref(0);
let intervalId: ReturnType<typeof setInterval> | null = null;

// Swap the list in place: the rotation timer is untouched, so the current slide keeps its remaining time.
const sync = createSlideSync({
    version: props.slideVersion,
    async onUpdate(data) {
        await preload(data.images);
        const current = images.value[currentIndex.value];
        currentIndex.value = nextIndex(current, currentIndex.value, data.images);
        images.value = data.images;
    },
});

onMounted(() => {
    preload(images.value);

    intervalId = setInterval(async () => {
        // Only swap once the next image is loaded; until then the current slide stays visible.
        const next = (currentIndex.value + 1) % images.value.length;
        await preload([images.value[next]], 3000);
        currentIndex.value = next % images.value.length;
    }, 8000);

    sync.start();
});

onBeforeUnmount(() => {
    sync.stop();

    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <div class="slideshow">
        <Transition name="fade">
            <img :key="images[currentIndex]" :src="images[currentIndex]" alt="" class="slide-image" />
        </Transition>
    </div>
</template>

<style scoped>
.slideshow {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: #000;
}

.slide-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Crossfade: the new slide fades in on top while the old one stays fully opaque underneath until removed. */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 1.5s ease-in-out;
}

.fade-enter-active {
    z-index: 1;
}

.fade-enter-from {
    opacity: 0;
}

.fade-leave-from,
.fade-leave-to {
    opacity: 1;
}
</style>
