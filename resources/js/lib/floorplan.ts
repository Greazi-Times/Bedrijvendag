import { PhBeerStein, PhDoorOpen, PhForkKnife, PhInfo, PhMapPin } from '@phosphor-icons/vue';
import type { Component } from 'vue';

type StandLike = {
    code: string | number;
    stand_type?: 'company' | 'partner' | null;
};

type MapPointLike = {
    type: string;
};

/** Partner stands are shown as P1, P2, ...; company stands by their number. */
export function standDisplayCode(stand: StandLike): string {
    const code = String(stand.code).replace(/^P/i, '');
    return stand.stand_type === 'partner' ? `P${code}` : code;
}

/** Companies use the brand orange, partners the brand blue from the logo. */
export function standBadgeClass(stand: StandLike): string {
    return stand.stand_type === 'partner' ? 'bg-brand-blue text-white' : 'bg-brand text-[#0b0f19]';
}

export function mapPointIcon(point: MapPointLike): Component {
    return (
        {
            bar: PhBeerStein,
            info: PhInfo,
            lunch: PhForkKnife,
            entrance: PhDoorOpen,
        }[point.type] ?? PhMapPin
    );
}

/** Facility markers keep distinct, recognisable colors so they read as a legend on the plan. */
export function mapPointMarkerClass(point: MapPointLike): string {
    return (
        {
            bar: 'bg-amber-400 text-amber-950 ring-white',
            info: 'bg-sky-500 text-white ring-white',
            lunch: 'bg-emerald-500 text-white ring-white',
            entrance: 'bg-violet-500 text-white ring-white',
        }[point.type] ?? 'bg-slate-600 text-white ring-white'
    );
}
