export interface SlideData {
    version: string;
    images: string[];
}

export interface SlideSyncOptions {
    version: string;
    onUpdate: (data: SlideData) => void | Promise<void>;
    versionUrl?: string;
    dataUrl?: string;
    intervalMs?: number;
    maxBackoffMs?: number;
    timeoutMs?: number;
    fetchImpl?: typeof fetch;
}

const isVersion = (v: unknown): v is string => typeof v === 'string' && v !== '';

async function getJson(fetchImpl: typeof fetch, url: string, timeoutMs: number): Promise<unknown> {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetchImpl(url, { signal: controller.signal, headers: { Accept: 'application/json' }, cache: 'no-store' });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    } finally {
        clearTimeout(timer);
    }
}

/**
 * Polls a tiny version endpoint and only downloads the slide list when the version changed. One timer, one request
 * at a time; failures back off exponentially (never faster than the normal interval) and the last good data is kept.
 */
export function createSlideSync(options: SlideSyncOptions) {
    const {
        onUpdate,
        versionUrl = '/slides/version',
        dataUrl = '/slides/data',
        intervalMs = 60_000,
        maxBackoffMs = 600_000,
        timeoutMs = 10_000,
        fetchImpl = (...args: Parameters<typeof fetch>) => fetch(...args),
    } = options;

    let version = options.version;
    let failures = 0;
    let timer: ReturnType<typeof setTimeout> | null = null;
    let stopped = true;

    async function check() {
        const payload = (await getJson(fetchImpl, versionUrl, timeoutMs)) as { version?: unknown } | null;

        if (!isVersion(payload?.version)) {
            throw new Error('Invalid version response');
        }

        if (payload.version === version) {
            return;
        }

        const data = (await getJson(fetchImpl, dataUrl, timeoutMs)) as Partial<SlideData> | null;
        const images = Array.isArray(data?.images) ? data.images.filter((src): src is string => typeof src === 'string' && src !== '') : [];

        // An empty or malformed list never replaces valid slides.
        if (!isVersion(data?.version) || images.length === 0) {
            throw new Error('Invalid slide response');
        }

        await onUpdate({ version: data.version, images });
        version = data.version;
    }

    async function tick() {
        timer = null;

        try {
            await check();
            failures = 0;
        } catch {
            failures++;
        }

        if (!stopped) {
            const delay = Math.min(intervalMs * 2 ** failures, maxBackoffMs);
            timer = setTimeout(tick, failures === 0 ? intervalMs : Math.max(intervalMs, delay));
        }
    }

    return {
        start() {
            if (stopped) {
                stopped = false;
                timer = setTimeout(tick, intervalMs);
            }
        },
        stop() {
            stopped = true;

            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
        },
    };
}

/** Index to show after the list changed: stay on the current slide if it survived, else the slide now in its place. */
export function nextIndex(current: string | undefined, currentIndex: number, images: string[]): number {
    const found = current === undefined ? -1 : images.indexOf(current);

    return found >= 0 ? found : Math.min(currentIndex, images.length - 1);
}

/** Warm the browser cache; a failed or slow image never blocks the update. */
export function preload(images: string[], timeoutMs = 10_000): Promise<unknown> {
    return Promise.allSettled(
        images.map(
            (src) =>
                new Promise<void>((resolve) => {
                    const img = new Image();
                    const done = () => resolve();
                    img.onload = img.onerror = done;
                    img.src = src;
                    setTimeout(done, timeoutMs);
                }),
        ),
    );
}
