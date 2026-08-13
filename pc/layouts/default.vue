<template>
  <div class="min-h-screen flex flex-col">
    <AppTopBar />
    <AppHeader />
    <AppNavMenu />
    <main class="flex-1">
      <slot />
    </main>
    <AppFooter />
    <BackToTop />
  </div>
</template>
