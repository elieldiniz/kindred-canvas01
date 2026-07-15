<flux:dropdown position="bottom" align="start">
    <button class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-2 py-1.5 hover:bg-white/10 hover:border-white/20 transition-all duration-300" data-test="sidebar-menu-button">
        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-primary to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-[0_0_10px_rgba(99,54,255,0.3)]">
            {{ auth()->user()->initials() }}
        </div>
        <span class="text-sm font-bold text-white leading-tight pr-2">{{ auth()->user()->name }}</span>
        <span class="material-symbols-outlined text-[16px] text-white/50 pr-1" style="font-variation-settings: 'FILL' 1, 'wght' 400;">expand_more</span>
    </button>

    <flux:menu class="!bg-[#081425] border border-white/10 shadow-2xl shadow-black/50 backdrop-blur-xl min-w-[220px]">
        <div class="flex items-center gap-3 px-3 py-3 text-start text-sm border-b border-white/5 mb-1">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-purple-600 flex items-center justify-center text-sm font-bold text-white shadow-[0_0_10px_rgba(99,54,255,0.3)]">
                {{ auth()->user()->initials() }}
            </div>
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate font-bold text-white">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate text-white/50 text-xs">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        
        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="hover:bg-white/5 text-white/80 hover:text-white transition-colors">
            {{ __('Settings') }}
        </flux:menu.item>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer hover:bg-white/5 text-white/80 hover:text-white transition-colors"
                data-test="logout-button"
            >
                {{ __('Log out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
