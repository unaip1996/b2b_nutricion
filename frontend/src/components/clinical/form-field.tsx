import React, { type ReactNode } from "react"

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

export interface TextInputProps extends React.InputHTMLAttributes<HTMLInputElement> {}

export function TextInput({ className, type = "text", ...props }: TextInputProps) {
  return (
    <input
      type={type}
      className={`${baseInputClasses} ${className || ''}`.trim()}
      {...props}
    />
  )
}

export interface TextAreaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {}

export function TextArea({ className, rows = 4, ...props }: TextAreaProps) {
  return (
    <textarea
      rows={rows}
      className={`${baseInputClasses} resize-none leading-relaxed ${className || ''}`.trim()}
      {...props}
    />
  )
}

export interface SelectInputProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  options: string[]
}

export function SelectInput({ options, className, ...props }: SelectInputProps) {
  return (
    <select className={`${baseInputClasses} ${className || ''}`.trim()} {...props}>
      <option value="" disabled hidden>Selecciona una opción</option>
      {options.map((option) => (
        <option key={option} value={option}>
          {option}
        </option>
      ))}
    </select>
  )
}
