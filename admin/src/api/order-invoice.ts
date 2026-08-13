import { myRequest } from '@/utils/request'

export interface OrderInvoiceInfo {
  id: number
  order_id: number
  order_no: string
  user_id: number
  type: 'personal' | 'company' | 'vat'
  invoice_type: 'electronic' | 'paper'
  title: string
  tax_no: string
  bank_name: string
  bank_account: string
  company_address: string
  company_phone: string
  recipient_name: string
  recipient_phone: string
  recipient_email: string
  amount: number
  content: string
  status: 'pending' | 'processing' | 'issued' | 'cancelled'
  file_url: string
  admin_remark: string
  issued_at: string | null
  created_at: string
  updated_at: string
}

export interface OrderInvoiceStats {
  pending: number
  processing: number
  issued: number
  cancelled: number
  total: number
}

export const orderInvoiceApi = {
  getList(params: Record<string, any>) {
    return myRequest.get('/adminapi/order/invoice', { params })
  },
  getStats() {
    return myRequest.get('/adminapi/order/invoice/stats')
  },
  getDetail(id: number) {
    return myRequest.get(`/adminapi/order/invoice/${id}`)
  },
  process(id: number, data: { admin_remark?: string }) {
    return myRequest.post(`/adminapi/order/invoice/${id}/process`, data)
  },
  issue(id: number, data: { file_url: string; admin_remark?: string }) {
    return myRequest.post(`/adminapi/order/invoice/${id}/issue`, data)
  },
  cancel(id: number, data: { admin_remark?: string }) {
    return myRequest.post(`/adminapi/order/invoice/${id}/cancel`, data)
  },
  update(id: number, data: Record<string, any>) {
    return myRequest.put(`/adminapi/order/invoice/${id}`, data)
  },
  delete(id: number) {
    return myRequest.delete(`/adminapi/order/invoice/${id}`)
  },
}
