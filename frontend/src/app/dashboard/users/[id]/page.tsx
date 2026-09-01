"use client";

import { useParams } from "next/navigation";
import { UserForm } from "@/components/UserForm";

export default function EditUserPage() {
    const params = useParams();
    const id = params?.id as string;

    return (
        <UserForm 
            apiUrl={`/api/users/${id}`}
            redirectUrl="/dashboard/users"
        />
    );
}