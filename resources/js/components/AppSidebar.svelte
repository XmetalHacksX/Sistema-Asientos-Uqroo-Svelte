<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import BookOpen from 'lucide-svelte/icons/book-open';
    import FolderGit2 from 'lucide-svelte/icons/folder-git-2';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';
    import { page } from '@inertiajs/svelte';
    import type { PageProps } from '@inertiajs/core';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems = $derived.by((): NavItem[] => {
        const items: NavItem[] = [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];

        return items;
    });

    const isSuperAdmin = $derived((page.props as any).auth?.user?.roles?.includes('super-admin') ?? false);
    const canValidateTickets = $derived(
        isSuperAdmin ||
        ((page.props as any).auth?.user?.roles?.includes('validador') ?? false) ||
        ((page.props as any).auth?.user?.permissions?.includes('validate-tickets') ?? false)
    );

    const adminNavItems = $derived.by((): NavItem[] => {
        let items: NavItem[] = [
            { title: 'Campus', href: '/admin/campuses' },
            { title: 'Edificios', href: '/admin/buildings' },
            { title: 'Eventos', href: '/admin/events' },
        ];

        if (canValidateTickets) {
            items.push(
                { title: 'Validar Boletos (QR)', href: '/admin/boletos/escanear' }
            );
        }

        if (isSuperAdmin) {
            items.push(
                { title: 'Salas/Teatros', href: '/admin/spaces' },
                { title: 'Roles y Permisos', href: '/admin/roles' }
            );
        }

        return items;
    });

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/svelte-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#svelte',
            icon: BookOpen,
        },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
        <NavMain label="Administración" items={adminNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
