export type CompanyDialogData = {
    name: string;
    kind: string;
    stand?: string | null;
    logoUrl?: string | null;
    description?: string | null;
    educations?: string[] | null;
    sectors?: string[] | null;
    showSectors?: boolean;
    websiteUrl?: string | null;
};
