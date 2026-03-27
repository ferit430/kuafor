// mobile/lib/screens/booking_screen.dart
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class BookingScreen extends StatefulWidget {
  final int salonId;
  final List<int> serviceIds;
  final int staffId;

  const BookingScreen({
    super.key,
    required this.salonId,
    required this.serviceIds,
    required this.staffId,
  });

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  DateTime _selectedDate = DateTime.now().add(const Duration(days: 1));
  String? _selectedSlot;
  List<String> _availableSlots = [];
  bool _isLoadingSlots = true;
  bool _isBooking = false;

  @override
  void initState() {
    super.initState();
    _fetchSlots();
  }

  void _fetchSlots() async {
    setState(() => _isLoadingSlots = true);
    try {
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final response = await ApiService.get('available_slots.php', params: {
        'date': dateStr,
        'salon_id': widget.salonId.toString(),
        'staff_id': widget.staffId.toString(),
        'service_ids': widget.serviceIds.join(','),
      });
      
      if (mounted) {
        setState(() {
          _availableSlots = List<String>.from(response['data']['slots'] ?? []);
          _selectedSlot = null; // Reset selection when date changes
          _isLoadingSlots = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoadingSlots = false);
    }
  }

  void _confirmBooking() async {
    if (_selectedSlot == null) return;
    
    setState(() => _isBooking = true);
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    final timeStr = "$_selectedSlot:00";

    try {
      final response = await ApiService.post('appointments.php', {
        'salon_id': widget.salonId,
        'service_ids': widget.serviceIds,
        'staff_id': widget.staffId,
        'date': dateStr,
        'start_time': timeStr,
      });

      if (response['status'] == 201) {
        if (!mounted) return;
        _showSuccessDialog();
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message'] ?? 'Randevu alınamadı.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Bağlantı hatası oluştu.')),
        );
      }
    } finally {
      if (mounted) setState(() => _isBooking = false);
    }
  }

  void _showSuccessDialog() {
    showGeneralDialog(
      context: context,
      barrierDismissible: false,
      barrierColor: Colors.black87,
      transitionDuration: const Duration(milliseconds: 300),
      pageBuilder: (ctx, anim1, anim2) => Center(
        child: Container(
          margin: const EdgeInsets.symmetric(horizontal: 40),
          padding: const EdgeInsets.all(32),
          decoration: AppTheme.glassDecoration,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.check_circle_outline, color: AppTheme.primaryColor, size: 80),
              const SizedBox(height: 24),
              const Text('BAŞARILI!', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white, decoration: TextDecoration.none)),
              const SizedBox(height: 16),
              const Text(
                'Randevu talebiniz başarıyla oluşturuldu. İşletme onayladığında bildirim alacaksınız.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.white70, fontWeight: FontWeight.normal, decoration: TextDecoration.none),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
                  child: const Text('TAMAM'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        title: const Text('RANDEVUYU ONAYLA', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
        backgroundColor: Colors.transparent,
      ),
      body: Stack(
        children: [
          Container(decoration: AppTheme.meshGradient),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSummaryCard(),
                  const SizedBox(height: 32),
                  
                  _buildSectionHeader('Tarih Seçin', Icons.calendar_today_outlined),
                  _buildDateSelector(),
                  const SizedBox(height: 24),
                  
                  _buildSectionHeader('Müsait Saatler', Icons.access_time_outlined),
                  _buildSlotSelector(),
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: (_selectedSlot != null && !_isBooking) ? _confirmBooking : null,
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 20),
              backgroundColor: AppTheme.primaryColor,
            ),
            child: _isBooking 
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Text('RANDEVUYU ONAYLA', style: TextStyle(letterSpacing: 1)),
          ),
        ),
      ),
    );
  }

  Widget _buildSummaryCard() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: AppTheme.glassDecoration,
      child: Column(
        children: [
          _buildInfoRow(Icons.calendar_month, 'Seçili Tarih', DateFormat('dd MMMM yyyy').format(_selectedDate)),
          const Padding(padding: EdgeInsets.symmetric(vertical: 12), child: Divider(color: Colors.white10)),
          _buildInfoRow(Icons.access_time, 'Seçili Saat', _selectedSlot ?? 'Lütfen Seçiniz'),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(color: AppTheme.primaryColor.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
          child: Icon(icon, color: AppTheme.primaryColor, size: 20),
        ),
        const SizedBox(width: 16),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(color: Colors.white38, fontSize: 12)),
            Text(value, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
      ],
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppTheme.primaryColor),
          const SizedBox(width: 8),
          Text(title, style: const TextStyle(color: Colors.white60, fontSize: 13, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _buildDateSelector() {
    return GestureDetector(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: _selectedDate,
          firstDate: DateTime.now(),
          lastDate: DateTime.now().add(const Duration(days: 30)),
          builder: (context, child) => Theme(
            data: Theme.of(context).copyWith(colorScheme: const ColorScheme.dark(primary: AppTheme.primaryColor)),
            child: child!,
          ),
        );
        if (picked != null) {
          setState(() => _selectedDate = picked);
          _fetchSlots();
        }
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        decoration: BoxDecoration(color: Colors.white.withOpacity(0.05), borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white10)),
        child: Row(
          children: [
            Text(DateFormat('dd MMMM yyyy', 'tr_TR').format(_selectedDate), style: const TextStyle(fontWeight: FontWeight.w500)),
            const Spacer(),
            const Icon(Icons.arrow_drop_down, color: Colors.white30),
          ],
        ),
      ),
    );
  }

  Widget _buildSlotSelector() {
    if (_isLoadingSlots) return const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator(color: AppTheme.primaryColor, strokeWidth: 2)));
    if (_availableSlots.isEmpty) return const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Müsait randevu saati bulunamadı.', style: TextStyle(color: Colors.white24, fontSize: 13))));

    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: _availableSlots.map((slot) {
        final isSelected = _selectedSlot == slot;
        return GestureDetector(
          onTap: () => setState(() => _selectedSlot = slot),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: isSelected ? AppTheme.primaryColor : Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isSelected ? AppTheme.primaryColor : Colors.white10),
              boxShadow: isSelected ? [BoxShadow(color: AppTheme.primaryColor.withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))] : null,
            ),
            child: Text(slot, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? Colors.white : Colors.white70, fontSize: 13)),
          ),
        );
      }).toList(),
    );
  }
}
