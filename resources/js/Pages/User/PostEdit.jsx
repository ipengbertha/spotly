import { Head } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import PostForm from '../../Components/PostForm';

export default function PostEdit({ categories, post }) {
    return (
        <UserLayout>
            <Head title="Edit Postingan" />
            <h1 className="mb-4 text-2xl font-bold">Edit Postingan</h1>
            {post.rejection_reason && (
                <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">
                    <strong>Alasan penolakan:</strong> {post.rejection_reason}
                </div>
            )}
            <PostForm categories={categories} post={post} />
        </UserLayout>
    );
}