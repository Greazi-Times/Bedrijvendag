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

    intervalId = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % images.value.length;
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
        <Transition name="fade" mode="out-in">
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
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Fade animation */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}
</style>
