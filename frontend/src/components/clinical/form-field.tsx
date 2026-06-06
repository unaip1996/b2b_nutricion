import type { ReactNode } from "react"

interface FormFieldProps {
  label: string
  htmlFor?: string
  children: ReactNode
}

export function FormField({ label, htmlFor, children }: FormFieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={htmlFor} className="text-xs font-medium text-slate-600">
        {label}
      </label>
      {children}
    </div>
  )
}

const baseInputClasses =
  "w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20"

export function TextInput({
  id,
  defaultValue,
  placeholder,
  type = "text",
}: {
  id?: string
  defaultValue?: string
  placeholder?: string
  type?: string
}) {
  return (
    <input
      id={id}
      type={type}
      defaultValue={defaultValue}
      placeholder={placeholder}
      className={baseInputClasses}
    />
  )
}

export function TextArea({
  id,
  defaultValue,
  placeholder,
  rows = 4,
}: {
  id?: string
  defaultValue?: string
  placeholder?: string
  rows?: number
}) {
  return (
    <textarea
      id={id}
      rows={rows}
      defaultValue={defaultValue}
      placeholder={placeholder}
      className={`${baseInputClasses} resize-none leading-relaxed`}
    />
  )
}

export function SelectInput({
  id,
  defaultValue,
  options,
}: {
  id?: string
  defaultValue?: string
  options: string[]
}) {
  return (
    <select id={id} defaultValue={defaultValue} className={baseInputClasses}>
      {options.map((option) => (
        <option key={option} value={option}>
          {option}
        </option>
      ))}
    </select>
  )
}
