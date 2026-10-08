import type { Directive } from 'vue';

type SpotlightElement = HTMLElement & { __spotlight?: (event: PointerEvent) => void };

/**
 * v-spotlight: feeds the pointer position into --mx / --my so `.spot`
 * surfaces can draw a glow under the cursor. Mouse/pen only; touch users
 * get the static styling. Writes CSS variables, so no Vue re-renders.
 */
export const spotlight: Directive<SpotlightElement> = {
    mounted(el) {
        const handler = (event: PointerEvent) => {
            if (event.pointerType === 'touch') return;
            const rect = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            el.style.setProperty('--my', `${event.clientY - rect.top}px`);
        };

        el.__spotlight = handler;
        el.addEventListener('pointermove', handler, { passive: true });
    },
    unmounted(el) {
        if (el.__spotlight) el.removeEventListener('pointermove', el.__spotlight);
    },
    getSSRProps() {
        return {};
    },
};
