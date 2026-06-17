import { KnowledgeBaseHeader } from "@/components/knowledge-base/knowledge-base-header"
import { KnowledgeBaseToolbar } from "@/components/knowledge-base/knowledge-base-toolbar"
import { DocumentsTable } from "@/components/knowledge-base/documents-table"

export default function Page() {
  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <div className="mx-auto max-w-6xl px-6 py-10">
        <KnowledgeBaseHeader />
        <KnowledgeBaseToolbar />
        <DocumentsTable />
      </div>
    </main>
  )
}
