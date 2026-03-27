// mobile/lib/screens/salon_detail_screen.dart
import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'booking_screen.dart';

class SalonDetailScreen extends StatefulWidget {
  final int salonId;
  const SalonDetailScreen({super.key, required this.salonId});

  @override
  State<SalonDetailScreen> createState() => _SalonDetailScreenState();
}

class _SalonDetailScreenState extends State<SalonDetailScreen> {
  Map<String, dynamic>? _salon;
  List<dynamic> _services = [];
  List<dynamic> _staff = [];
  bool _isLoading = true;
  List<int> _selectedServiceIds = [];
  int? _selectedStaffId;

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  void _fetchDetails() async {
    try {
      final salonRes = await ApiService.get('salons.php', params: {'id': widget.salonId.toString()});
      final servicesRes = await ApiService.get('services.php', params: {'salon_id': widget.salonId.toString()});
      final staffRes = await ApiService.get('staff.php', params: {'salon_id': widget.salonId.toString()});
      
      if (mounted) {
        setState(() {
          if (salonRes['data'] is List) {
            _salon = salonRes['data'][0];
          } else {
            _salon = salonRes['data'];
          }
          _services = servicesRes['data'] ?? [];
          _staff = staffRes['data'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _toggleService(int id) {
    setState(() {
      if (_selectedServiceIds.contains(id)) {
        _selectedServiceIds.remove(id);
      } else {
        _selectedServiceIds.add(id);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        leading: CircleAvatar(
          backgroundColor: Colors.black26,
          child: IconButton(
            icon: const Icon(Icons.arrow_back, color: Colors.white),
            onPressed: () => Navigator.pop(context),
          ),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : Stack(
              children: [
                Container(decoration: AppTheme.meshGradient),
                SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Banner Image
                      Container(
                        height: 300,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          image: DecorationImage(
                            image: _salon?['cover_image'] != null
                                ? NetworkImage('${ApiService.baseMediaUrl}${_salon?['cover_image']}')
                                : const NetworkImage('https://via.placeholder.com/800x600'),
                            fit: BoxFit.cover,
                          ),
                        ),
                        child: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [Colors.black.withOpacity(0.4), Colors.transparent, AppTheme.backgroundColor],
                            ),
                          ),
                        ),
                      ),
                      
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _salon?['name']?.toString().toUpperCase() ?? 'SALON ADI',
                              style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, letterSpacing: 1),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                const Icon(Icons.location_on, size: 16, color: AppTheme.primaryColor),
                                const SizedBox(width: 4),
                                Text(
                                  '${_salon?['city']} / ${_salon?['district'] ?? ''}',
                                  style: const TextStyle(color: Colors.white60),
                                ),
                              ],
                            ),
                            const SizedBox(height: 32),
                            
                            _buildSectionHeader('Hizmetlerimiz', Icons.spa_outlined),
                            ..._services.map((s) => _buildSelectionCard(
                                  title: s['name'],
                                  subtitle: '${s['price']} TL • ${s['duration_minutes']} dk',
                                  id: int.parse(s['id'].toString()),
                                  isSelected: _selectedServiceIds.contains(int.parse(s['id'].toString())),
                                  onTap: (val) => _toggleService(val),
                                )),
                                
                            const SizedBox(height: 32),
                            _buildSectionHeader('Personelimiz', Icons.person_outline),
                            ..._staff.map((st) => _buildSelectionCard(
                                  title: st['name'],
                                  subtitle: st['expertise'] ?? 'Uzman',
                                  id: int.parse(st['id'].toString()),
                                  isSelected: _selectedStaffId == int.parse(st['id'].toString()),
                                  onTap: (val) => setState(() => _selectedStaffId = val),
                                )),
                                
                            const SizedBox(height: 120), // Bottom padding for FAB
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: _selectedServiceIds.isNotEmpty && _selectedStaffId != null
          ? Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => BookingScreen(
                        salonId: widget.salonId,
                        serviceIds: _selectedServiceIds,
                        staffId: _selectedStaffId!,
                      ),
                    ),
                  ),
                  child: const Text('RANDEVU OLUŞTUR'),
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Row(
        children: [
          Icon(icon, size: 24, color: AppTheme.primaryColor),
          const SizedBox(width: 12),
          Text(
            title,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildSelectionCard({
    required String title,
    required String subtitle,
    required int id,
    required bool isSelected,
    required Function(int) onTap,
  }) {
    return GestureDetector(
      onTap: () => onTap(id),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryColor.withOpacity(0.15) : Colors.white.withOpacity(0.03),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? AppTheme.primaryColor : Colors.white.withOpacity(0.1)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 4),
                  Text(subtitle, style: const TextStyle(color: Colors.white54, fontSize: 13)),
                ],
              ),
            ),
            if (isSelected) const Icon(Icons.check_circle, color: AppTheme.primaryColor),
          ],
        ),
      ),
    );
  }
}
