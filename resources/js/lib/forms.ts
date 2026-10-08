import { nextTick } from 'vue';

/** After a failed submit, move focus to the first invalid field so keyboard and screen reader users land on the error. */
export function focusFirstError(): void {
    nextTick(() => {
        const field = document.querySelector<HTMLElement>('[aria-invalid="true"]');
        field?.focus({ preventScroll: false });
    });
}
