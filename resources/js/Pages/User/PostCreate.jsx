import { Head } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import PostForm from '../../Components/PostForm';

export default function PostCreate({ categories }) {
    return (
        <UserLayout>
            <Head title="Buat Postingan" />
            <h1 className="mb-4 text-2xl font-bold">Buat Postingan</h1>
            <PostForm categories={categories} />
        </UserLayout>
    );
}