import { createRequire } from "node:module";

import { defineConfig, type Plugin } from "vite";
import uniModule from "@dcloudio/vite-plugin-uni";
import UnoCSS from "unocss/vite";

// CJS interop: @dcloudio/vite-plugin-uni uses exports.default in CJS
const uni = (uniModule as any).default || uniModule;
const rootRequire = createRequire(import.meta.url);

/** uni / Rollup 从软链路径解析时看不到 pnpm 的包内依赖，按 importer 再 resolve 一次。 */
function resolvePnpmNested(): Plugin {
  return {
    name: "resolve-pnpm-nested",
    enforce: "pre",
    resolveId(id, importer) {
      if (!id.startsWith("@vue/devtools-") && id !== "pinia") {
        return null;
      }
      const tries = [];
      if (importer) {
        tries.push(createRequire(importer));
      }
      tries.push(rootRequire);
      for (const req of tries) {
        try {
          return req.resolve(id);
        } catch {
          /* next */
        }
      }
      return null;
    },
  };
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [resolvePnpmNested(), UnoCSS(), uni()],
  resolve: {
    dedupe: ["vue", "pinia", "@vue/devtools-api", "@vue/devtools-kit", "@vue/devtools-shared"],
  },
  build: {
    sourcemap: false,
    reportCompressedSize: false,
  },
  // H5 开发模式代理，解决浏览器跨域问题
  server: {
    proxy: {
      "/api": {
        target: "http://localhost:9001",
        changeOrigin: true,
      },
      "/storage": {
        target: "http://localhost:9001",
        changeOrigin: true,
      },
    },
  },
});
