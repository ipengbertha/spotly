import AdminLayout from './AdminLayout';
import UserLayout from './UserLayout';
import PublicLayout from './PublicLayout';
import Footer from '../Components/Landing/Footer';

// Halaman yang bisa dibuka admin, siswa, maupun tamu memakai layout sesuai pembacanya
export default function ViewerLayout({ viewer, title, children }) {
    if (viewer === 'admin') return <AdminLayout title={title}>{children}</AdminLayout>;
    if (viewer === 'user') return <UserLayout>{children}</UserLayout>;

    return (
        <PublicLayout>
            <div className="bg-linear-to-b from-peach/60 to-rose-200 px-6 py-10">{children}</div>
            <Footer />
        </PublicLayout>
    );
}
