import { KnowledgeBaseHeader } from "@/components/knowledge-base-header"
import { KnowledgeBaseToolbar } from "@/components/knowledge-base-toolbar"
import { DocumentsTable } from "@/components/documents-table"

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
