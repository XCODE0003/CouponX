export interface StoreCardData {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    logo_dark: string | null;
    description: string | null;
    coupons_count: number | null;
    url: string;
    go_url: string;
}

export interface StoreFullData extends StoreCardData {
    about: string | null;
    countries: string[] | null;
    meta_title: string | null;
    meta_description: string | null;
    categories: CategoryData[];
}

export type CouponType = 'code' | 'deal' | 'sale';

export interface CouponData {
    id: number;
    type: CouponType;
    title: string;
    description: string | null;
    terms: string | null;
    code: string | null;
    has_code: boolean;
    discount_type: string | null;
    discount_value: number | null;
    used_count: number;
    is_featured: boolean;
    is_exclusive: boolean;
    is_verified: boolean;
    expires_at: string | null;
    out_url: string;
    store?: {
        name: string;
        slug: string;
        logo: string | null;
        logo_dark: string | null;
        url: string;
    };
}

export interface CategoryData {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    stores_count: number | null;
    coupons_count: number | null;
    url: string;
}

export interface BlogPostData {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    cover_image: string | null;
    published_at: string | null;
    author: string | null;
    url: string;
    body?: string | null;
    meta_title?: string | null;
    meta_description?: string | null;
}

export interface Pagination {
    current: number;
    last: number;
    total: number;
}
