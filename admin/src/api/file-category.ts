import { myRequest } from '@/utils/request'

/**
 * 文件分类 API
 */
export const fileCategoryApi = {
    /** 获取分类树 */
    getTree() {
        return myRequest.get('/adminapi/system/file-category/tree')
    },

    /** 创建分类 */
    create(data: { name: string; parent_id?: number; sort?: number }) {
        return myRequest.post('/adminapi/system/file-category', data)
    },

    /** 更新分类 */
    update(id: number, data: { name?: string; parent_id?: number; sort?: number }) {
        return myRequest.put(`/adminapi/system/file-category/${id}`, data)
    },

    /** 删除分类 */
    delete(id: number) {
        return myRequest.delete(`/adminapi/system/file-category/${id}`)
    },
}
