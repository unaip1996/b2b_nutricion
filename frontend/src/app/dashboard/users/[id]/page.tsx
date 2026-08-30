"use client";

import { useParams } from "next/navigation";
import { UserForm } from "@/components/UserForm";

export default function EditUserPage() {
    const params = useParams();
    const id = params?.id as string;

    return (
        <UserForm 
            apiUrl={`${process.env.NEXT_PUBLIC_API_URL}/api/users/${id}`}
            redirectUrl="/dashboard/users"
        />
    );
}