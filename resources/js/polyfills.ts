// typescript
// File: `resources/js/polyfills.ts`
if (typeof window !== 'undefined' && !(window.crypto && (window.crypto as any).randomUUID)) {
    (window.crypto as any).randomUUID = function randomUUID() {
        const bytes = (window.crypto && window.crypto.getRandomValues)
            ? window.crypto.getRandomValues(new Uint8Array(16))
            : (() => {
                const arr = new Uint8Array(16);
                for (let i = 0; i < 16; i++) arr[i] = Math.floor(Math.random() * 256);
                return arr;
            })();

        // Per RFC4122 v4
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
        return `${hex.substr(0,8)}-${hex.substr(8,4)}-${hex.substr(12,4)}-${hex.substr(16,4)}-${hex.substr(20,12)}`;
    };
}
