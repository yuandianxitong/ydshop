import { getToken } from '@/utils/auth'

export interface UploadImageResult {
  url: string
}

/**
 * 上传图片，返回服务端 URL。
 * 后端：POST /api/common/upload/image (multipart, file 字段, 限 2MB, jpeg/png/gif/webp)
 */
export function uploadImage(filePath: string): Promise<UploadImageResult> {
  return new Promise((resolve, reject) => {
    const token = getToken()
    uni.uploadFile({
      url: '/api/common/upload/image',
      filePath,
      name: 'file',
      header: token ? { Authorization: `Bearer ${token}` } : {},
      success: (res) => {
        try {
          const body = typeof res.data === 'string' ? JSON.parse(res.data) : res.data
          if (body && body.code === 200 && body.data?.url) {
            resolve({ url: body.data.url as string })
          } else {
            reject(new Error(body?.message || '上传失败'))
          }
        } catch (e: any) {
          reject(new Error(e?.message || '响应解析失败'))
        }
      },
      fail: (err) => reject(new Error(err?.errMsg || '上传失败')),
    })
  })
}

export const commonApi = {
  uploadImage,
}
