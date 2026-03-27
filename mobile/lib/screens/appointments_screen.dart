// mobile/lib/screens/appointments_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class AppointmentsScreen extends StatefulWidget {
  const AppointmentsScreen({super.key});

  @override
  State<AppointmentsScreen> createState() => _AppointmentsScreenState();
}

class _AppointmentsScreenState extends State<AppointmentsScreen> {
  List<dynamic> _appointments = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchAppointments();
  }

  void _fetchAppointments() async {
    try {
      final response = await ApiService.get('appointments.php');
      if (response['status'] == 200) {
        setState(() {
          _appointments = response['data'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text('RANDEVULARIM'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : _appointments.isEmpty
              ? _buildEmptyState()
              : ListView.builder(
                  padding: const EdgeInsets.all(20),
                  itemCount: _appointments.length,
                  itemBuilder: (context, index) {
                    final appointment = _appointments[index];
                    return _buildAppointmentCard(appointment);
                  },
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.calendar_today_outlined, size: 80, color: Colors.white.withOpacity(0.1)),
          const SizedBox(height: 16),
          Text(
            'Henüz randevunuz bulunmuyor.',
            style: TextStyle(color: Colors.white.withOpacity(0.5)),
          ),
        ],
      ),
    );
  }

  Widget _buildAppointmentCard(dynamic appointment) {
    final status = appointment['status'] ?? 'pending';
    Color statusColor;
    String statusText;

    switch (status) {
      case 'confirmed':
        statusColor = Colors.greenAccent;
        statusText = 'ONAYLANDI';
        break;
      case 'completed':
        statusColor = Colors.blueAccent;
        statusText = 'TAMAMLANDI';
        break;
      case 'cancelled':
        statusColor = Colors.redAccent;
        statusText = 'İPTAL EDİLDİ';
        break;
      default:
        statusColor = Colors.orangeAccent;
        statusText = 'BEKLEMEDE';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(20),
      decoration: AppTheme.glassDecoration,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                appointment['salon_name']?.toString().toUpperCase() ?? 'SALON',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, letterSpacing: 1),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: statusColor.withOpacity(0.3)),
                ),
                child: Text(
                  statusText,
                  style: TextStyle(color: statusColor, fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(color: Colors.white10),
          const SizedBox(height: 12),
          _buildInfoRow(Icons.cut, appointment['service_name'] ?? 'Hizmet'),
          const SizedBox(height: 8),
          _buildInfoRow(Icons.person_outline, appointment['staff_name'] ?? 'Personel'),
          const SizedBox(height: 8),
          _buildInfoRow(
            Icons.access_time,
            '${_formatDate(appointment['appointment_date'])} - ${appointment['start_time'].toString().substring(0, 5)}',
          ),
          const SizedBox(height: 16),
          if (status == 'pending')
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => _cancelAppointment(int.parse(appointment['id'].toString())),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: Colors.redAccent, width: 0.5),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                child: const Text('RANDEVUYU İPTAL ET', style: TextStyle(color: Colors.redAccent, fontSize: 12)),
              ),
            ),
        ],
      ),
    );
  }

  void _cancelAppointment(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1A1A1A),
        title: const Text('İptal Onayı'),
        content: const Text('Bu randevuyu iptal etmek istediğinize emin misiniz?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('HAYIR', style: TextStyle(color: Colors.white60))),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('EVET', style: TextStyle(color: Colors.redAccent))),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final response = await ApiService.post('appointments.php', {
          'id': id,
          'status': 'cancelled',
          '_method': 'PATCH', // Helper or direct PATCH call
        });

        if (response['status'] == 200) {
          _fetchAppointments();
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Randevu başarıyla iptal edildi.')),
          );
        }
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Hata oluştu.')),
        );
      }
    }
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 16, color: AppTheme.primaryColor.withOpacity(0.7)),
        const SizedBox(width: 10),
        Text(text, style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 14)),
      ],
    );
  }

  String _formatDate(String dateStr) {
    final date = DateTime.parse(dateStr);
    return DateFormat('dd MMMM yyyy', 'tr_TR').format(date);
  }
}
