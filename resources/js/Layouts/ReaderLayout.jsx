import PublicLayout from './PublicLayout';
import Footer from '../Components/Landing/Footer';

// Layout halaman baca artikel untuk semua pembaca (tamu, siswa, admin):
// header seperti landing page (dengan tanggal), tanpa sidebar.
export default function ReaderLayout({ children }) {
    return (
        <PublicLayout showDate appMenu>
            <div className="bg-linear-to-b from-peach/60 to-rose-200 px-4 py-8 sm:px-6 sm:py-10">{children}</div>
            <Footer />
        </PublicLayout>
    );
}