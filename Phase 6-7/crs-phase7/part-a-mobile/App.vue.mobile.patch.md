<!-- App.vue — MOBILE PATCH
     Three additions to the existing App.vue from crs-sidebar.zip:

     1. Add the mobile overlay div
     2. Add the mobile top bar
     3. Add mobile open/close state to the sidebar

     ── STEP 1: Add these two divs BEFORE <aside class="sidebar"> ─────────

     <div class="mobile-overlay" :class="{ visible: mobileOpen }"
       @click="mobileOpen = false" />

     <div class="mobile-topbar">
       <button class="mobile-menu-btn" @click="mobileOpen = true">☰</button>
       <span style="font-size:14px;font-weight:500">CRS ECCO</span>
     </div>

     ── STEP 2: Add :class binding to <aside class="sidebar"> ─────────────

     BEFORE:  <aside class="sidebar">
     AFTER:   <aside class="sidebar" :class="{ 'mobile-open': mobileOpen, collapsed: isCollapsed }">

     ── STEP 3: Add mobileOpen ref and close-on-navigate watcher ──────────

     In <script setup>, add:

     import { ref, watch } from 'vue'
     import { useRoute }   from 'vue-router'

     const mobileOpen = ref(false)
     const route      = useRoute()

     // Close sidebar when navigating on mobile
     watch(() => route.path, () => { mobileOpen.value = false })

     ── STEP 4: Add NavItem close handler ─────────────────────────────────

     In the <nav> element, add @click to close on mobile:

     BEFORE:  <nav class="nav" @click.stop>
     AFTER:   <nav class="nav" @click.stop @click="mobileOpen = false">

     ── Full minimal diff for reference ───────────────────────────────────

     The only lines that change in App.vue are:

       // In <script setup>:
       + import { useRoute }     from 'vue-router'
       + const mobileOpen = ref(false)
       + const route      = useRoute()
       + watch(() => route.path, () => { mobileOpen.value = false })

       // In <template> — add before <aside>:
       + <div class="mobile-overlay" :class="{ visible: mobileOpen }" @click="mobileOpen = false" />
       + <div class="mobile-topbar">
       +   <button class="mobile-menu-btn" @click="mobileOpen = true">☰</button>
       +   <span style="font-size:14px;font-weight:500">CRS ECCO</span>
       + </div>

       // Change <aside> opening tag:
       - <aside class="sidebar">
       + <aside class="sidebar" :class="{ 'mobile-open': mobileOpen, collapsed: isCollapsed }">

       // Change <nav> to close sidebar on link click:
       - <nav class="nav" @click.stop>
       + <nav class="nav" @click.stop @click="mobileOpen = false">

     ── No other changes needed ───────────────────────────────────────────
     All visual behaviour (overlay, slide-in, top bar visibility) is handled
     by responsive.css which must be imported in main.css or main.js:

       import '@/assets/responsive.css'
-->
